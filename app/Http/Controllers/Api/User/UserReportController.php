<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\StudentReportService;
use Illuminate\Http\Request;

class UserReportController extends Controller
{
    public function __construct(
        private StudentReportService $studentReportService
    ) {}

    public function summary(Request $request)
    {
        return response()->json($this->studentReportService->summary($request->user('users'), $request->all()));
    }

    public function assessments(Request $request)
    {
        return response()->json($this->studentReportService->assessments($request->user('users'), $request->all()));
    }

    public function attempts(Request $request)
    {
        return response()->json($this->studentReportService->attempts($request->user('users'), $request->all()));
    }

    public function questions(Request $request)
    {
        return response()->json($this->studentReportService->questions($request->user('users'), $request->all()));
    }

    public function progress(Request $request)
    {
        return response()->json($this->studentReportService->progress($request->user('users'), $request->all()));
    }
}
