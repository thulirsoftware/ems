<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use App\Models\Assessment;
use App\Models\Batch;
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

        $batchId = $request->query('batch_id');

        if ($assessment->is_batch_wise && empty($batchId)) {
            return response()->json([
                'message' => 'batch_id is required for batch-wise assessments'
            ], 422);
        }

        // resolve batch
        if ($assessment->is_batch_wise) {
            $batch = Batch::where('id', $batchId)
                ->where('assessment_id', $assessment->id)
                ->first();
        } else {
            $batch = Batch::where('assessment_id', $assessment->id)
                ->where('name', 'individual_batch_' . $assessment->id)
                ->first();
        }

        if (!$batch) {
            return response()->json([
                'message' => 'Invalid batch_id for this assessment'
            ], 422);
        }

        $assignedUserIds = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->pluck('user_id')
            ->toArray();

        $users = \App\Models\User::select('id', 'name')
            ->get()
            ->map(function ($user) use ($assignedUserIds, $assessment, $batch) {

                $conflict = \DB::table('assessment_assignments as aa')
                    ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
                    ->join('batches as b', 'b.id', '=', 'aa.batch_id') // ✅ FIXED
                    ->where('aa.user_id', $user->id)
                    ->where('a.id', '!=', $assessment->id)
                    ->whereDate('b.publish_date', $batch->publish_date)
                    ->where(function ($q) use ($batch) {
                        $q->where('b.start_time', '<', $batch->end_time)
                            ->where('b.end_time', '>', $batch->start_time);
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
            'batch_id' => 'nullable|exists:batches,id',
        ]);

        $assessment = Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        if ($assessment->is_batch_wise) {
            if (empty($validated['batch_id'])) {
                return response()->json(['message' => 'batch_id is required'], 422);
            }

            $batch = Batch::where('id', $validated['batch_id'])
                ->where('assessment_id', $assessment->id)
                ->first();

        } else {
            $batch = Batch::where('assessment_id', $assessment->id)
                ->where('name', 'individual_batch_' . $assessment->id)
                ->first();
        }

        if (!$batch) {
            return response()->json([
                'message' => 'Batch not found or invalid for this assessment'
            ], 422);
        }

        $assignments = [];
        $conflicts = [];

        foreach ($validated['user_ids'] as $userId) {

            $conflict = \DB::table('assessment_assignments as aa')
                ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
                ->join('batches as b', 'b.id', '=', 'aa.batch_id') // ✅ FIXED
                ->where('aa.user_id', $userId)
                ->where('a.id', '!=', $assessment->id)
                ->whereDate('b.publish_date', $batch->publish_date)
                ->where(function ($q) use ($batch) {
                    $q->where('b.start_time', '<', $batch->end_time)
                        ->where('b.end_time', '>', $batch->start_time);
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

            $assignment = AssessmentAssignment::withTrashed()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $userId)
                ->first();

            if ($assignment) {
                $assignment->restore();
            } else {
                $assignment = AssessmentAssignment::create([
                    'assessment_id' => $assessment->id,
                    'user_id' => $userId,
                    'batch_id' => $batch->id
                ]);
            }

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
            'message' => 'Assignment completed',
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
            ->firstOrFail();

        $deleted = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->whereIn('user_id', $validated['user_ids'])
            ->delete();

        return response()->json([
            'message' => 'Users unassigned successfully',
            'deleted_count' => $deleted,
        ]);
    }
}