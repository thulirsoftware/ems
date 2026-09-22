<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\StudentQuestionService;
use Illuminate\Http\Request;

class UserQuestionController extends Controller
{
    public function __construct(
        private StudentQuestionService $studentQuestionService
    ) {}

    public function index(Request $request, $assessment_id)
    {
        return response()->json($this->studentQuestionService->index($request->user('users'), $assessment_id));
    }
}
