<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReExamService;
use Illuminate\Http\Request;

class ReExamController extends Controller
{
    public function __construct(
        private ReExamService $reExamService
    ) {}

    public function createReExam(Request $request)
    {
        $result = $this->reExamService->create($request->user('admins'), $request->all());

        return response()->json([
            'message' => 'Re-exam created successfully',
            ...$result,
        ], 201);
    }

    public function createFilteredReExam(Request $request)
    {
        $result = $this->reExamService->createFiltered($request->user('admins'), $request->all());

        return response()->json([
            'message' => 'Filtered re-exam created successfully',
            ...$result,
        ], 201);
    }
}
