<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentChoice;
use App\Models\AssessmentQuestion;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AssessmentQuestionController extends Controller
{
    // Prevent modifications after attempts exist
    private function isAssessmentLocked($assessment_id)
    {
        return AssessmentAttempt::where('assessment_id', $assessment_id)->exists();
    }

    public function index(Request $request, $assessment_id)
    {
        $admin = $request->user('admins');

        Assessment::where('id', $assessment_id)
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        $questions = AssessmentQuestion::where('assessment_id', $assessment_id)
            ->orderBy('order')
            ->get();

        return response()->json($questions);
    }

    public function store(Request $request, $assessment_id)
    {
        $admin = $request->user('admins');

        Assessment::where('id', $assessment_id)
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        if ($this->isAssessmentLocked($assessment_id)) {
            return response()->json([
                'message' => 'Cannot modify questions after assessment has been attempted'
            ], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:mcq,descriptive',
            'question_text' => 'required|string',
            'order' => 'required|integer',
            'config' => 'required_if:type,case_based|array',
        ]);

        $question = AssessmentQuestion::create([
            'assessment_id' => $assessment_id,
            ...$validated,
        ]);

        return response()->json($question, 201);
    }

    public function update(Request $request, $id)
    {
        $admin = $request->user('admins');

        $question = AssessmentQuestion::whereHas('assessment', function ($q) use ($admin) {
            $q->where('admin_id', $admin->id);
        })->where('id', $id)->firstOrFail();

        if ($this->isAssessmentLocked($question->assessment_id)) {
            return response()->json([
                'message' => 'Cannot modify questions after assessment has been attempted'
            ], 403);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:mcq,descriptive',
            'question_text' => 'sometimes|string',
            'order' => 'nullable|integer',
            'config' => 'nullable|array',
        ]);

        $question->update($validated);

        return response()->json($question);
    }

    public function destroy(Request $request, $id)
    {
        $admin = $request->user('admins');

        $question = AssessmentQuestion::whereHas('assessment', function ($q) use ($admin) {
            $q->where('admin_id', $admin->id);
        })->where('id', $id)->firstOrFail();

        if ($this->isAssessmentLocked($question->assessment_id)) {
            return response()->json([
                'message' => 'Cannot delete questions after assessment has been attempted'
            ], 403);
        }

        $question->delete();

        return response()->json([
            'message' => 'Question deleted'
        ]);
    }

    public function indexWithChoices(Request $request, $assessment_id)
    {
        $admin = $request->user('admins');

        Assessment::where('id', $assessment_id)
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        $questions = AssessmentQuestion::with('choices')
            ->where('assessment_id', $assessment_id)
            ->orderBy('order')
            ->get();

        return response()->json($questions);
    }

    public function showWithChoices(Request $request, $question_id)
    {
        $admin = $request->user('admins');

        $question = AssessmentQuestion::with('choices')
            ->whereHas('assessment', function ($q) use ($admin) {
                $q->where('admin_id', $admin->id);
            })
            ->where('id', $question_id)
            ->firstOrFail();

        return response()->json($question);
    }

    public function storeWithChoices(Request $request, $assessment_id)
    {
        $admin = $request->user('admins');

        Assessment::where('id', $assessment_id)
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        if ($this->isAssessmentLocked($assessment_id)) {
            return response()->json([
                'message' => 'Cannot modify questions after assessment has been attempted'
            ], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:mcq',
            'question_text' => 'required|string',
            'order' => 'nullable|integer',
            'choices' => 'required|array|size:4',
            'choices.*.option' => 'required|string',
            'choices.*.is_correct' => 'required|boolean',
            'choices.*.order' => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($validated, $assessment_id) {

            $question = AssessmentQuestion::create([
                'assessment_id' => $assessment_id,
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

            return response()->json(
                $question->load('choices'),
                201
            );
        });
    }

    public function updateWithChoices(Request $request, $question_id)
    {
        $admin = $request->user('admins');

        $question = AssessmentQuestion::whereHas('assessment', function ($q) use ($admin) {
            $q->where('admin_id', $admin->id);
        })->where('id', $question_id)->firstOrFail();

        if ($this->isAssessmentLocked($question->assessment_id)) {
            return response()->json([
                'message' => 'Cannot modify questions after assessment has been attempted'
            ], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:mcq',
            'question_text' => 'required|string',
            'order' => 'nullable|integer',
            'choices' => 'required|array|size:4',
            'choices.*.option' => 'required|string',
            'choices.*.is_correct' => 'required|boolean',
        ]);

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

            return response()->json(
                $question->load('choices')
            );
        });
    }

    public function bulkStoreQuestions(Request $request, $assessment_id)
    {
        $admin = $request->user('admins');

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx'
        ]);

        $assessment = Assessment::where('id', $assessment_id)
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        if (AssessmentAttempt::where('assessment_id', $assessment_id)->exists()) {
            return response()->json([
                'message' => 'Cannot modify questions after assessment has been attempted'
            ], 403);
        }

        $rows = Excel::toArray([], $request->file('file'))[0];

        if (count($rows) < 2) {
            return response()->json(['message' => 'File is empty'], 400);
        }

        $header = array_map('strtolower', $rows[0]);

        $inserted = 0;
        $errors = [];

        DB::beginTransaction();

        try {

            // Start order from last + 1
            $currentOrder = AssessmentQuestion::where('assessment_id', $assessment_id)
                ->max('order') ?? 0;

            foreach (array_slice($rows, 1) as $index => $row) {

                try {
                    $data = array_combine($header, $row);

                    if (empty($data['question_text'])) {
                        $errors[] = ['row' => $index + 2, 'error' => 'Question text required'];
                        continue;
                    }

                    // Choices (0–4)
                    $choices = array_values(array_filter([
                        $data['choice_1'] ?? null,
                        $data['choice_2'] ?? null,
                        $data['choice_3'] ?? null,
                        $data['choice_4'] ?? null,
                    ], fn($c) => !empty($c)));

                    $type = count($choices) > 0 ? 'mcq' : 'descriptive';

                    // Validate correct choice for MCQ
                    if (!empty($choices)) {

                        if (empty($data['correct_choice'])) {
                            $errors[] = ['row' => $index + 2, 'error' => 'Correct choice required for MCQ'];
                            continue;
                        }

                        $matches = array_filter($choices, fn($c) => $c == $data['correct_choice']);

                        if (count($matches) !== 1) {
                            $errors[] = ['row' => $index + 2, 'error' => 'Exactly one valid correct choice required'];
                            continue;
                        }
                    }

                    // Increment order
                    $currentOrder++;

                    $question = AssessmentQuestion::create([
                        'assessment_id' => $assessment_id,
                        'type' => $type,
                        'question_text' => $data['question_text'],
                        'order' => $currentOrder,
                    ]);

                    // add order for choices
                    foreach (array_values($choices) as $index => $choice) {
                        AssessmentChoice::create([
                            'question_id' => $question->id,
                            'option' => $choice,
                            'is_correct' => ($choice == $data['correct_choice']),
                            'order' => $index + 1,
                        ]);
                    }

                    $inserted++;

                } catch (\Exception $e) {
                    $errors[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Bulk insert failed'], 500);
        }

        return response()->json([
            'message' => 'Bulk questions upload completed',
            'inserted' => $inserted,
            'errors' => $errors
        ]);
    }
}