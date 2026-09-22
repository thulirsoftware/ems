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

        return DB::transaction(function () use ($validated, $assessment) {
            $question = AssessmentQuestion::create([
                'assessment_id' => $assessment->id,
                'type' => $validated['type'],
                'question_text' => $validated['question_text'],
                'order' => $validated['order'] ?? null,
            ]);

            foreach ($validated['choices'] as $choice) {
                AssessmentChoice::create([
                    'question_id' => $question->id,
                    'option' => $choice['option'],
                    'is_correct' => $choice['is_correct'],
                    'order' => $choice['order'] ?? null,
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
        ])->validate();

        return DB::transaction(function () use ($validated, $question) {
            $question->update([
                'type' => $validated['type'],
                'question_text' => $validated['question_text'],
                'order' => $validated['order'] ?? null,
            ]);

            $question->choices()->delete();

            foreach ($validated['choices'] as $choice) {
                $question->choices()->create($choice);
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
                    if (empty($row['question_text'])) {
                        $errors[] = ['row' => $index + 2, 'error' => 'Question text required'];
                        continue;
                    }

                    $choices = array_values(array_filter([
                        $row['choice_1'] ?? null,
                        $row['choice_2'] ?? null,
                        $row['choice_3'] ?? null,
                        $row['choice_4'] ?? null,
                    ], fn ($c) => !empty($c)));

                    $type = count($choices) > 0 ? 'mcq' : 'descriptive';

                    if (!empty($choices)) {
                        if (empty($row['correct_choice'])) {
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
