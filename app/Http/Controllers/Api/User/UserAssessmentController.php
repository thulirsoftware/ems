<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\StudentAssessmentService;
use Illuminate\Http\Request;

class UserAssessmentController extends Controller
{
    public function __construct(
        private StudentAssessmentService $studentAssessmentService
    ) {}

    public function upcoming(Request $request)
    {
        return response()->json($this->studentAssessmentService->upcoming($request->user('users')));
    }

    public function today(Request $request)
    {
        return response()->json($this->studentAssessmentService->today($request->user('users')));
    }

    public function running(Request $request)
    {
        return response()->json($this->studentAssessmentService->running($request->user('users')));
    }

    public function completed(Request $request)
    {
        return response()->json($this->studentAssessmentService->completed($request->user('users')));
    }

    public function missed(Request $request)
    {
        return response()->json($this->studentAssessmentService->missed($request->user('users')));
    }

    public function show(Request $request, $id)
    {
        return response()->json($this->studentAssessmentService->get($request->user('users'), $id));
    }
}
