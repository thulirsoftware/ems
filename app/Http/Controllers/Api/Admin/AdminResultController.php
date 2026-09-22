<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ResultService;
use Illuminate\Http\Request;

class AdminResultController extends Controller
{
    public function __construct(
        private ResultService $resultService
    ) {}

    public function finishedAssessments(Request $request)
    {
        return response()->json($this->resultService->finishedAssessments($request->user('admins')));
    }

    public function usersByAssessment(Request $request, $assessment_id)
    {
        return response()->json(
            $this->resultService->usersByAssessment($request->user('admins'), $assessment_id, $request->query('batch_id'))
        );
    }

    public function userAnswersForGrading(Request $request, $assessment_id, $user_id)
    {
        return response()->json(
            $this->resultService->answersForGrading($request->user('admins'), $assessment_id, $user_id, $request->query('batch_id'))
        );
    }

    public function gradeAnswer(Request $request, $assessment_id, $user_id, $question_id)
    {
        $request->validate(['is_correct' => 'required|boolean']);

        $result = $this->resultService->gradeAnswer($request->user('admins'), [
            'assessment_id' => $assessment_id,
            'user_id' => $user_id,
            'question_id' => $question_id,
            'is_correct' => $request->is_correct,
            'batch_id' => $request->query('batch_id'),
        ]);

        if ($result['final']) {
            return response()->json([
                'message' => 'Answer graded. Final score calculated.',
                'score' => $result['score'],
            ]);
        }

        return response()->json([
            'message' => 'Answer graded successfully',
            'graded' => $result['graded'],
            'total' => $result['total'],
        ]);
    }

    public function userResult(Request $request, $assessment_id, $user_id)
    {
        return response()->json(
            $this->resultService->userResult($request->user('admins'), $assessment_id, $user_id, $request->query('batch_id'))
        );
    }

    public function rankList(Request $request, $assessment_id)
    {
        return response()->json(
            $this->resultService->rankList($request->user('admins'), $assessment_id, $request->query('batch_id'))
        );
    }
}
