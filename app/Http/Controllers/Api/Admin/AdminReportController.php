<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function __construct(
        private AdminReportService $reportService
    ) {}

    public function summary(Request $request)
    {
        return response()->json($this->reportService->summary($request->user('admins'), $request->all()));
    }

    public function assessments(Request $request)
    {
        return response()->json($this->reportService->assessments($request->user('admins'), $request->all()));
    }

    public function batches(Request $request)
    {
        return response()->json($this->reportService->batches($request->user('admins'), $request->all()));
    }

    public function users(Request $request)
    {
        return response()->json($this->reportService->users($request->user('admins'), $request->all()));
    }

    public function questions(Request $request)
    {
        return response()->json($this->reportService->questions($request->user('admins'), $request->all()));
    }

    public function attempts(Request $request)
    {
        return response()->json($this->reportService->attempts($request->user('admins'), $request->all()));
    }
}
