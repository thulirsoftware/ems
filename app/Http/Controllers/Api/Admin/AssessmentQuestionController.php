<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentChoice;
use App\Models\AssessmentQuestion;
use App\Models\Assessment;
use DB;
use Illuminate\Http\Request;

class AssessmentQuestionController extends Controller
{
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
}
