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
            ->map(function ($user) use ($assignedUserIds, $assessment) {

                $conflict = \DB::table('assessment_assignments as aa')
                    ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
                    ->where('aa.user_id', $user->id)
                    ->where('a.id', '!=', $assessment->id)
                    ->whereNotNull('a.publish_date')
                    ->whereDate('a.publish_date', $assessment->publish_date)
                    ->whereNotNull('a.start_time')
                    ->whereNotNull('a.end_time')
                    ->where(function ($q) use ($assessment) {
                        $q->where('a.start_time', '<', $assessment->end_time)
                            ->where('a.end_time', '>', $assessment->start_time);
                    })
                    ->select('a.id', 'a.title')
                    ->first();

                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'assigned' => in_array($user->id, $assignedUserIds),
                    'has_time_conflict' => (bool) $conflict,
                    'conflicting_assessment_id' => $conflict?->id,
                    'conflicting_assessment_title' => $conflict?->title,
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
        $conflicts = [];

        foreach ($validated['user_ids'] as $userId) {

            // Check time conflict before assigning
            $conflict = \DB::table('assessment_assignments as aa')
                ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
                ->where('aa.user_id', $userId)
                ->where('a.id', '!=', $assessment->id)
                ->whereNotNull('a.publish_date')
                ->whereDate('a.publish_date', $assessment->publish_date)
                ->whereNotNull('a.start_time')
                ->whereNotNull('a.end_time')
                ->where(function ($q) use ($assessment) {
                    $q->where('a.start_time', '<', $assessment->end_time)
                        ->where('a.end_time', '>', $assessment->start_time);
                })
                ->select('a.id', 'a.title')
                ->first();

            if ($conflict) {
                $conflicts[] = [
                    'user_id' => $userId,
                    'conflicting_assessment_id' => $conflict->id,
                    'conflicting_assessment_title' => $conflict->title,
                ];
                continue;
            }

            $assignment = AssessmentAssignment::firstOrCreate([
                'assessment_id' => $assessment->id,
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
            'message' => 'Assessment assignment completed',
            'assigned' => $assignments,
            'blocked_due_to_conflict' => $conflicts,
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
