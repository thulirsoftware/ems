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
    private function isBatchLocked($batch_id)
    {
        return \App\Models\AssessmentAttempt::where('batch_id', $batch_id)->exists();
    }

    private function resolveBatch($assessment, $batchId = null)
    {
        if ($assessment->is_batch_wise) {

            if (!$batchId) {
                abort(422, 'batch_id is required for batch-wise assessments');
            }

            return Batch::where('id', $batchId)
                ->where('assessment_id', $assessment->id)
                ->firstOrFail();
        }

        // 🔥 always latest batch for non-batch-wise (re-exam safe)
        return Batch::where('assessment_id', $assessment->id)
            ->latest('id')
            ->firstOrFail();
    }

    public function usersWithAssignmentStatus(Request $request)
    {
        $admin = $request->user('admins');

        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
        ]);

        $assessment = Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        $batch = $this->resolveBatch($assessment, $request->query('batch_id'));

        $assignedUserIds = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('batch_id', $batch->id) // ✅ FIX
            ->pluck('user_id')
            ->toArray();

        $users = \App\Models\User::select('id', 'name')
            ->get()
            ->map(function ($user) use ($assignedUserIds, $assessment, $batch) {

                $conflict = \DB::table('assessment_assignments as aa')
                    ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
                    ->join('batches as b', 'b.id', '=', 'aa.batch_id')
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

        $batch = $this->resolveBatch($assessment, $validated['batch_id'] ?? null);

        // 🔒 lock after attempt
        if ($this->isBatchLocked($batch->id)) {
            return response()->json([
                'message' => 'Cannot assign users after batch has been attempted'
            ], 403);
        }

        $assignments = [];
        $conflicts = [];

        foreach ($validated['user_ids'] as $userId) {

            $conflict = \DB::table('assessment_assignments as aa')
                ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
                ->join('batches as b', 'b.id', '=', 'aa.batch_id')
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

            // 🔥 batch-aware assignment
            $assignment = AssessmentAssignment::withTrashed()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $userId)
                ->where('batch_id', $batch->id)
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
            'batch_id' => 'nullable|exists:batches,id',
        ]);

        $assessment = Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        $batch = $this->resolveBatch($assessment, $validated['batch_id'] ?? null);

        // 🔒 lock after attempt
        if ($this->isBatchLocked($batch->id)) {
            return response()->json([
                'message' => 'Cannot unassign users after batch has been attempted'
            ], 403);
        }

        // 🔥 batch-safe delete
        $deleted = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('batch_id', $batch->id)
            ->whereIn('user_id', $validated['user_ids'])
            ->delete();

        return response()->json([
            'message' => 'Users unassigned successfully',
            'deleted_count' => $deleted,
        ]);
    }
}