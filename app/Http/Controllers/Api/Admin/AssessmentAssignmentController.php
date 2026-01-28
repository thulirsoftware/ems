<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use App\Models\Assessment;
use Illuminate\Http\Request;

class AssessmentAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user('admins');

        $assignments = AssessmentAssignment::whereHas('assessment', function ($q) use ($admin) {
            $q->where('admin_id', $admin->id);
        })->get();

        return response()->json($assignments);
    }

    public function store(Request $request)
    {
        $admin = $request->user('admins');

        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'user_id' => 'required|exists:users,id',
        ]);

        Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        $assignment = AssessmentAssignment::create($validated);

        return response()->json($assignment, 201);
    }

    public function destroy(Request $request, $id)
    {
        $admin = $request->user('admins');

        $assignment = AssessmentAssignment::whereHas('assessment', function ($q) use ($admin) {
            $q->where('admin_id', $admin->id);
        })->where('id', $id)->firstOrFail();

        $assignment->delete();

        return response()->json([
            'message' => 'Assignment removed'
        ]);
    }
}
