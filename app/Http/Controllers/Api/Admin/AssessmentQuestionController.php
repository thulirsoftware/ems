<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentQuestion;
use App\Models\Assessment;
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
            'type' => 'required|string',
            'question_text' => 'required|string',
            'order' => 'nullable|integer',
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
            'type' => 'sometimes|string',
            'question_text' => 'sometimes|string',
            'order' => 'nullable|integer',
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
}
