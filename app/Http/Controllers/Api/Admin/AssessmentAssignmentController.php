<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use App\Models\Assessment;
use Illuminate\Http\Request;

class AssessmentAssignmentController extends Controller
{
    public function usersWithAssignmentStatus(Request $request)
    {
        $admin = $request->user('admins');

        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
        ]);

        Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        $assignedUserIds = AssessmentAssignment::where('assessment_id', $validated['assessment_id'])
            ->pluck('user_id')
            ->toArray();

        $users = \App\Models\User::select('id', 'name')
            ->get()
            ->map(function ($user) use ($assignedUserIds) {
                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'assigned' => in_array($user->id, $assignedUserIds),
                ];
            });

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $admin = $request->user('admins');

        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        $assignments = [];

        foreach ($validated['user_ids'] as $userId) {
            $assignments[] = AssessmentAssignment::firstOrCreate([
                'assessment_id' => $validated['assessment_id'],
                'user_id' => $userId,
            ]);
        }

        return response()->json([
            'message' => 'Assessment assigned to students successfully',
            'assignments' => $assignments,
        ], 201);
    }

    public function destroy(Request $request)
    {
        $admin = $request->user('admins');

        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        $deleted = AssessmentAssignment::where('assessment_id', $validated['assessment_id'])
            ->whereIn('user_id', $validated['user_ids'])
            ->delete();

        return response()->json([
            'message' => 'Users unassigned successfully',
            'deleted_count' => $deleted,
        ]);
    }
}
