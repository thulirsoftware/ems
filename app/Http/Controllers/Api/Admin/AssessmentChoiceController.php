<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentChoice;
use App\Models\AssessmentQuestion;
use DB;
use Illuminate\Http\Request;

class AssessmentChoiceController extends Controller
{
    public function index(Request $request, $question_id)
    {
        $admin = $request->user('admins');

        AssessmentQuestion::whereHas('assessment', function ($q) use ($admin) {
            $q->where('admin_id', $admin->id);
        })->where('id', $question_id)->firstOrFail();

        $choices = AssessmentChoice::where('question_id', $question_id)
            ->orderBy('order')
            ->get();

        return response()->json($choices);
    }

    public function store(Request $request, $question_id)
    {
        $admin = $request->user('admins');

        AssessmentQuestion::whereHas('assessment', function ($q) use ($admin) {
            $q->where('admin_id', $admin->id);
        })->where('id', $question_id)->firstOrFail();

        $validated = $request->validate([
            'option' => 'required|string',
            'is_correct' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        $choice = AssessmentChoice::create([
            'question_id' => $question_id,
            ...$validated,
        ]);

        return response()->json($choice, 201);
    }

    public function update(Request $request, $id)
    {
        $admin = $request->user('admins');

        $choice = AssessmentChoice::whereHas('question.assessment', function ($q) use ($admin) {
            $q->where('admin_id', $admin->id);
        })->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'option' => 'sometimes|string',
            'is_correct' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        $choice->update($validated);

        return response()->json($choice);
    }

    public function destroy(Request $request, $id)
    {
        $admin = $request->user('admins');

        $choice = AssessmentChoice::whereHas('question.assessment', function ($q) use ($admin) {
            $q->where('admin_id', $admin->id);
        })->where('id', $id)->firstOrFail();

        $choice->delete();

        return response()->json([
            'message' => 'Choice deleted'
        ]);
    }

    public function storeWithChoices(Request $request, $assessment_id)
    {
        $admin = $request->user('admins');

        Assessment::where('id', $assessment_id)
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        $validated = $request->validate([
            'type' => 'required|string',
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
            'type' => 'required|string',
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
