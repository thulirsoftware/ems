<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AssignmentService;
use Illuminate\Http\Request;

class AssessmentAssignmentController extends Controller
{
    public function __construct(
        private AssignmentService $assignmentService
    ) {}

    public function usersWithAssignmentStatus(Request $request)
    {
        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
        ]);

        return response()->json(
            $this->assignmentService->status($request->user('admins'), $validated['assessment_id'], $request->query('batch_id'))
        );
    }

    public function store(Request $request)
    {
        $result = $this->assignmentService->assign($request->user('admins'), $request->all());

        return response()->json([
            'message' => 'Assignment completed',
            ...$result,
        ], 201);
    }

    public function destroy(Request $request)
    {
        $deleted = $this->assignmentService->unassign($request->user('admins'), $request->all());

        return response()->json([
            'message' => 'Users unassigned successfully',
            'deleted_count' => $deleted,
        ]);
    }
}
