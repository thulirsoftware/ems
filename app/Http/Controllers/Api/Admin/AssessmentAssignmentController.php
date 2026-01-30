<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use App\Models\Assessment;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AssessmentAssignmentController extends Controller
{
    public function usersWithAssignmentStatus(Request $request)
    {
        $admin = $request->user('admins');

        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
        ]);

        $assessment = Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->first();

        if (!$assessment) {
            return response()->json([
                'message' => 'Assessment not found or you do not have access'
            ], 404);
        }

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

        $assessment = Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->first();

        if (!$assessment) {
            return response()->json([
                'message' => 'Assessment not found or you do not have access'
            ], 404);
        }

        $assignments = [];

        foreach ($validated['user_ids'] as $userId) {

            $assignment = AssessmentAssignment::firstOrCreate([
                'assessment_id' => $validated['assessment_id'],
                'user_id' => $userId,
            ]);

            NotificationService::notifyUser(
                $userId,
                'assessment_assigned',
                'New assessment assigned',
                "You have been assigned: {$assessment->title}",
                [
                    'assessment_id' => $assessment->id,
                    'admin_id' => $admin->id
                ]
            );

            $assignments[] = $assignment;
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

        $assessment = Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->first();

        if (!$assessment) {
            return response()->json([
                'message' => 'Assessment not found or you do not have access'
            ], 404);
        }

        $deleted = AssessmentAssignment::where('assessment_id', $validated['assessment_id'])
            ->whereIn('user_id', $validated['user_ids'])
            ->delete();

        return response()->json([
            'message' => 'Users unassigned successfully',
            'deleted_count' => $deleted,
        ]);
    }
}
