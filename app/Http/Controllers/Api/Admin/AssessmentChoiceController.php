<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentChoice;
use App\Models\AssessmentQuestion;
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
}
