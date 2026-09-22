<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AssessmentService;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function __construct(
        private AssessmentService $assessmentService
    ) {}

    public function index(Request $request)
    {
        return response()->json($this->assessmentService->list($request->user('admins')));
    }

    public function store(Request $request)
    {
        $assessment = $this->assessmentService->create($request->user('admins'), $request->all());

        return response()->json([
            'message' => 'Assessment created successfully',
            'data' => $assessment,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        return response()->json($this->assessmentService->get($request->user('admins'), $id));
    }

    public function update(Request $request, $id)
    {
        $assessment = $this->assessmentService->update($request->user('admins'), $id, $request->all());

        return response()->json([
            'message' => 'Assessment updated successfully',
            'data' => $assessment,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $this->assessmentService->delete($request->user('admins'), $id);

        return response()->json([
            'message' => 'Assessment deleted successfully',
        ]);
    }

    public function library(Request $request)
    {
        return response()->json($this->assessmentService->library($request->user('admins')));
    }

    public function upcoming(Request $request)
    {
        return response()->json($this->assessmentService->upcoming($request->user('admins')));
    }

    public function running(Request $request)
    {
        return response()->json($this->assessmentService->running($request->user('admins')));
    }
}
