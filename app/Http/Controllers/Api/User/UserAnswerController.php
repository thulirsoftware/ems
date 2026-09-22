<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\StudentAnswerService;
use Illuminate\Http\Request;

class UserAnswerController extends Controller
{
    public function __construct(
        private StudentAnswerService $answerService
    ) {}

    public function store(Request $request, $assessment_id)
    {
        $answer = $this->answerService->store($request->user('users'), $assessment_id, $request->all());

        return response()->json([
            'message' => 'Answer saved',
            'answer' => $answer,
        ]);
    }
}
