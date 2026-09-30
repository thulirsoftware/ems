<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentChoice;
use App\Models\AssessmentQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuestionService
{
    private function isLocked(int $assessmentId): bool
    {
        return AssessmentAttempt::where('assessment_id', $assessmentId)->exists();
    }

    // $assessmentId/$id are untyped — see the comment on
    // AssessmentService::findOwned() for why.
    private function ownedAssessment(Admin $admin, $assessmentId): Assessment
    {
        return Assessment::where('id', $assessmentId)->where('admin_id', $admin->id)->firstOrFail();
    }

    private function ownedQuestion(Admin $admin, $id): AssessmentQuestion
    {
        return AssessmentQuestion::whereHas('assessment', fn ($q) => $q->where('admin_id', $admin->id))
            ->where('id', $id)
            ->firstOrFail();
    }

    // Answering, evaluation and grading all follow the assessment's type, so a
    // question of the other type could never be answered or scored correctly.
    private function ensureTypeMatchesAssessment(Assessment $assessment, string $questionType): void
    {
        $assessmentType = $assessment->type?->slug;

        if (in_array($assessmentType, ['mcq', 'descriptive'], true) && $assessmentType !== $questionType) {
            abort(422, "This is a {$assessmentType} assessment — only {$assessmentType} questions can be added to it");
        }
    }

    // An MCQ question needs exactly one correct choice to be scorable.
    private function ensureSingleCorrectChoice(array $choices): void
    {
        $correct = collect($choices)->filter(fn ($choice) => filter_var($choice['is_correct'], FILTER_VALIDATE_BOOLEAN))->count();

        if ($correct !== 1) {
            abort(422, 'Exactly one choice must be marked correct');
        }
    }

    // "order" is NOT NULL, so an omitted order appends after the existing questions.
    private function nextOrder(int $assessmentId): int
    {
        return (int) (AssessmentQuestion::where('assessment_id', $assessmentId)->max('order') ?? 0) + 1;
    }

    public function list(Admin $admin, $assessmentId)
    {
        $this->ownedAssessment($admin, $assessmentId);

        return AssessmentQuestion::where('assessment_id', $assessmentId)->orderBy('order')->get();
    }

    public function listWithChoices(Admin $admin, $assessmentId)
    {
        $this->ownedAssessment($admin, $assessmentId);

        return AssessmentQuestion::with('choices')->where('assessment_id', $assessmentId)->orderBy('order')->get();
    }

    public function getWithChoices(Admin $admin, $id): AssessmentQuestion
    {
        return $this->ownedQuestion($admin, $id)->load('choices');
    }

    public function create(Admin $admin, $assessmentId, array $data): AssessmentQuestion
    {
        $assessment = $this->ownedAssessment($admin, $assessmentId);

        if ($this->isLocked($assessment->id)) {
            abort(403, 'Cannot modify questions after assessment has been attempted');
        }

        $validated = Validator::make($data, [
            'type' => 'required|in:mcq,descriptive',
            'question_text' => 'required|string',
            'order' => 'required|integer',
            'config' => 'nullable|array',
        ])->validate();

        $this->ensureTypeMatchesAssessment($assessment, $validated['type']);

        return AssessmentQuestion::create(['assessment_id' => $assessment->id, ...$validated]);
    }

    public function update(Admin $admin, $id, array $data): AssessmentQuestion
    {
        $question = $this->ownedQuestion($admin, $id);

        if ($this->isLocked($question->assessment_id)) {
            abort(403, 'Cannot modify questions after assessment has been attempted');
        }

        $validated = Validator::make($data, [
            'type' => 'sometimes|in:mcq,descriptive',
            'question_text' => 'sometimes|string',
            'order' => 'nullable|integer',
            'config' => 'nullable|array',
        ])->validate();

        // "order" is NOT NULL — a null means "leave it unchanged".
        if (array_key_exists('order', $validated) && $validated['order'] === null) {
            unset($validated['order']);
        }

        if (isset($validated['type'])) {
            $this->ensureTypeMatchesAssessment($question->assessment, $validated['type']);
        }

        $question->update($validated);

        return $question;
    }

    public function delete(Admin $admin, $id): void
    {
        $question = $this->ownedQuestion($admin, $id);

        if ($this->isLocked($question->assessment_id)) {
            abort(403, 'Cannot delete questions after assessment has been attempted');
        }

        $question->delete();
    }

    public function createWithChoices(Admin $admin, $assessmentId, array $data): AssessmentQuestion
    {
        $assessment = $this->ownedAssessment($admin, $assessmentId);

        if ($this->isLocked($assessment->id)) {
            abort(403, 'Cannot modify questions after assessment has been attempted');
        }

        $validated = Validator::make($data, [
            'type' => 'required|in:mcq',
            'question_text' => 'required|string',
            'order' => 'nullable|integer',
            'choices' => 'required|array|size:4',
            'choices.*.option' => 'required|string',
            'choices.*.is_correct' => 'required|boolean',
            'choices.*.order' => 'nullable|integer',
        ])->validate();

        $this->ensureTypeMatchesAssessment($assessment, 'mcq');
        $this->ensureSingleCorrectChoice($validated['choices']);

        return DB::transaction(function () use ($validated, $assessment) {
            $question = AssessmentQuestion::create([
                'assessment_id' => $assessment->id,
                'type' => $validated['type'],
                'question_text' => $validated['question_text'],
                'order' => $validated['order'] ?? $this->nextOrder($assessment->id),
            ]);

            foreach (array_values($validated['choices']) as $index => $choice) {
                AssessmentChoice::create([
                    'question_id' => $question->id,
                    'option' => $choice['option'],
                    'is_correct' => $choice['is_correct'],
                    'order' => $choice['order'] ?? $index + 1,
                ]);
            }

            return $question->load('choices');
        });
    }

    public function updateWithChoices(Admin $admin, $id, array $data): AssessmentQuestion
    {
        $question = $this->ownedQuestion($admin, $id);

        if ($this->isLocked($question->assessment_id)) {
            abort(403, 'Cannot modify questions after assessment has been attempted');
        }

        $validated = Validator::make($data, [
            'type' => 'required|in:mcq',
            'question_text' => 'required|string',
            'order' => 'nullable|integer',
            'choices' => 'required|array|size:4',
            'choices.*.option' => 'required|string',
            'choices.*.is_correct' => 'required|boolean',
            'choices.*.order' => 'nullable|integer',
        ])->validate();

        $this->ensureTypeMatchesAssessment($question->assessment, 'mcq');
        $this->ensureSingleCorrectChoice($validated['choices']);

        return DB::transaction(function () use ($validated, $question) {
            $question->update([
                'type' => $validated['type'],
                'question_text' => $validated['question_text'],
                // keep the question's position unless a new one is given
                'order' => $validated['order'] ?? $question->order,
            ]);

            $question->choices()->delete();

            foreach (array_values($validated['choices']) as $index => $choice) {
                $question->choices()->create([
                    'option' => $choice['option'],
                    'is_correct' => $choice['is_correct'],
                    'order' => $choice['order'] ?? $index + 1,
                ]);
            }

            return $question->load('choices');
        });
    }

    /**
     * Shared bulk-insert core. $rows is a plain array of
     * ['question_text' => ..., 'choice_1'..'choice_4' => ..., 'correct_choice' => ...],
     * regardless of whether the caller parsed them from an uploaded file
     * (AssessmentQuestionController) or received them directly (QuestionTool).
     */
    public function bulkCreate(Admin $admin, $assessmentId, array $rows): array
    {
        $assessment = $this->ownedAssessment($admin, $assessmentId);

        if ($this->isLocked($assessment->id)) {
            abort(403, 'Cannot modify questions after assessment has been attempted');
        }

        if (empty($rows)) {
            abort(400, 'File is empty');
        }

        $inserted = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            $currentOrder = AssessmentQuestion::where('assessment_id', $assessment->id)->max('order') ?? 0;

            foreach ($rows as $index => $row) {
                try {
                    // Spreadsheet cells can be numbers, and "0" is a legitimate
                    // answer — so test for blank strings, never empty().
                    $isBlank = fn ($value) => $value === null || trim((string) $value) === '';

                    if ($isBlank($row['question_text'] ?? null)) {
                        $errors[] = ['row' => $index + 2, 'error' => 'Question text required'];
                        continue;
                    }

                    $choices = array_values(array_filter([
                        $row['choice_1'] ?? null,
                        $row['choice_2'] ?? null,
                        $row['choice_3'] ?? null,
                        $row['choice_4'] ?? null,
                    ], fn ($c) => !$isBlank($c)));

                    $type = count($choices) > 0 ? 'mcq' : 'descriptive';

                    // the row's shape must match the assessment's type
                    $assessmentType = $assessment->type?->slug;

                    if ($assessmentType === 'mcq' && $type !== 'mcq') {
                        $errors[] = ['row' => $index + 2, 'error' => 'Choices are required in an MCQ assessment'];
                        continue;
                    }

                    if ($assessmentType === 'descriptive' && $type !== 'descriptive') {
                        $errors[] = ['row' => $index + 2, 'error' => 'Choices are not allowed in a descriptive assessment'];
                        continue;
                    }

                    if (!empty($choices)) {
                        if ($isBlank($row['correct_choice'] ?? null)) {
                            $errors[] = ['row' => $index + 2, 'error' => 'Correct choice required for MCQ'];
                            continue;
                        }

                        $matches = array_filter($choices, fn ($c) => $c == $row['correct_choice']);

                        if (count($matches) !== 1) {
                            $errors[] = ['row' => $index + 2, 'error' => 'Exactly one valid correct choice required'];
                            continue;
                        }
                    }

                    $currentOrder++;

                    $question = AssessmentQuestion::create([
                        'assessment_id' => $assessment->id,
                        'type' => $type,
                        'question_text' => $row['question_text'],
                        'order' => $currentOrder,
                    ]);

                    foreach (array_values($choices) as $choiceIndex => $choice) {
                        AssessmentChoice::create([
                            'question_id' => $question->id,
                            'option' => $choice,
                            'is_correct' => ($choice == $row['correct_choice']),
                            'order' => $choiceIndex + 1,
                        ]);
                    }

                    $inserted++;
                } catch (\Throwable $e) {
                    $errors[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            abort(500, 'Bulk insert failed');
        }

        return ['inserted' => $inserted, 'errors' => $errors];
    }
}
