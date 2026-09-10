<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use App\Models\Assessment;
use App\Services\NotificationService;
use App\Models\AssessmentAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentAssignmentController extends Controller
{
    private function isBatchLocked($batch_id)
    {
        return AssessmentAttempt::where('batch_id', $batch_id)->exists();
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

        // Same batch-resolution rule used by the admin results endpoints,
        // so an invalid/missing batch_id fails the same way (422) everywhere
        // instead of a 404 here and a 422 there.
        $result = resolve_batch($assessment, $request->query('batch_id'));

        if (isset($result['error'])) {
            return $result['error'];
        }

        $batch = $result['batch'];

        $assignedUserIds = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('batch_id', $batch->id)
            ->pluck('user_id')
            ->toArray();

        // Fetch every conflict in one query instead of one query per user.
        $conflictsByUser = collect();

        if ($assessment->scheduling_type !== Assessment::SCHEDULING_FLEXIBLE) {
            $conflictsByUser = DB::table('assessment_assignments as aa')
                ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
                ->join('batches as b', 'b.id', '=', 'aa.batch_id')
                ->where('a.id', '!=', $assessment->id)
                ->whereDate('b.publish_date', $batch->publish_date)
                ->where(function ($q) use ($batch) {
                    $q->where('b.start_time', '<', $batch->end_time)
                        ->where('b.end_time', '>', $batch->start_time);
                })
                ->select('aa.user_id', 'a.id', 'a.title')
                ->get()
                ->keyBy('user_id');
        }

        $users = User::select('id', 'name')
            ->get()
            ->map(function ($user) use ($assignedUserIds, $conflictsByUser) {

                $conflict = $conflictsByUser->get($user->id);

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

        $result = resolve_batch($assessment, $validated['batch_id'] ?? null);

        if (isset($result['error'])) {
            return $result['error'];
        }

        $batch = $result['batch'];

        // lock after attempt
        if ($this->isBatchLocked($batch->id)) {
            return response()->json([
                'message' => 'Cannot assign users after batch has been attempted'
            ], 403);
        }

        $assignments = [];
        $conflicts = [];

        foreach ($validated['user_ids'] as $userId) {

            $conflict = null;

            if ($assessment->scheduling_type !== Assessment::SCHEDULING_FLEXIBLE) {

                $conflict = DB::table('assessment_assignments as aa')
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
            }

            if ($conflict) {
                $conflicts[] = [
                    'user_id' => $userId,
                    'conflicting_assessment_id' => $conflict->id,
                    'conflicting_assessment_title' => $conflict->title,
                ];
                continue;
            }

            // batch-aware assignment
            $assignment = AssessmentAssignment::withTrashed()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $userId)
                ->where('batch_id', $batch->id)
                ->first();

            if ($assignment) {
                $assignment->restore();
            } else {
                // The find-then-create above isn't atomic, so a duplicate
                // request racing this one can still hit the unique
                // constraint; fall back to restoring/reusing it instead of
                // failing the whole batch of assignments.
                try {
                    $assignment = AssessmentAssignment::create([
                        'assessment_id' => $assessment->id,
                        'user_id' => $userId,
                        'batch_id' => $batch->id
                    ]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    $assignment = AssessmentAssignment::withTrashed()
                        ->where('assessment_id', $assessment->id)
                        ->where('user_id', $userId)
                        ->where('batch_id', $batch->id)
                        ->firstOrFail();

                    $assignment->restore();
                }
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

        $hideBatch = is_implicit_batch($assessment, $batch);

        return response()->json([
            'message' => 'Assignment completed',
            'assigned' => collect($assignments)->map(fn($assignment) => [
                ...$assignment->toArray(),
                'batch_id' => $hideBatch ? null : $assignment->batch_id,
            ]),
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

        $result = resolve_batch($assessment, $validated['batch_id'] ?? null);

        if (isset($result['error'])) {
            return $result['error'];
        }

        $batch = $result['batch'];

        // lock after attempt
        if ($this->isBatchLocked($batch->id)) {
            return response()->json([
                'message' => 'Cannot unassign users after batch has been attempted'
            ], 403);
        }

        // batch-safe delete
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