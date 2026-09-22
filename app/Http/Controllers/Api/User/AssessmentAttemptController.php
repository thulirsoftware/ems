<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\StudentAttemptService;
use Illuminate\Http\Request;

class AssessmentAttemptController extends Controller
{
    public function __construct(
        private StudentAttemptService $attemptService
    ) {}

    public function start(Request $request, $assessment_id)
    {
        $result = $this->attemptService->start($request->user('users'), $assessment_id);

        return response()->json([
            'message' => $result['message'],
            'attempt' => $result['attempt'],
        ], $result['status']);
    }

    public function submit(Request $request, $assessment_id)
    {
        $result = $this->attemptService->submit($request->user('users'), $assessment_id);

        return response()->json([
            'message' => 'Assessment submitted successfully',
            'score' => $result['score'],
            'correct' => $result['correct'],
            'total' => $result['total'],
        ]);
    }

    public function result(Request $request, $assessment_id)
    {
        return response()->json(
            $this->attemptService->result($request->user('users'), $assessment_id, $request->all())
        );
    }
}
