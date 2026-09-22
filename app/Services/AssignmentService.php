<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AssignmentService
{
    private function isBatchLocked(int $batchId): bool
    {
        return AssessmentAttempt::where('batch_id', $batchId)->exists();
    }

    private function ownedAssessment(Admin $admin, int $id): Assessment
    {
        return Assessment::where('id', $id)->where('admin_id', $admin->id)->firstOrFail();
    }

    // $batchId is untyped: it's an unvalidated query param, so it must flow
    // into resolve_batch() exactly as received — see the comment on
    // AssessmentService::findOwned() for why.
    public function status(Admin $admin, int $assessmentId, $batchId): array
    {
        $assessment = $this->ownedAssessment($admin, $assessmentId);

        $result = resolve_batch($assessment, $batchId);

        if (isset($result['error'])) {
            abort(422, $result['error']->getData()->message);
        }

        $batch = $result['batch'];

        $assignedUserIds = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('batch_id', $batch->id)
            ->pluck('user_id')
            ->toArray();

        $conflictsByUser = collect();

        if ($assessment->scheduling_type !== Assessment::SCHEDULING_FLEXIBLE) {
            $conflictsByUser = DB::table('assessment_assignments as aa')
                ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
                ->join('batches as b', 'b.id', '=', 'aa.batch_id')
                ->where('a.id', '!=', $assessment->id)
                ->whereDate('b.publish_date', $batch->publish_date)
                ->where(fn ($q) => $q->where('b.start_time', '<', $batch->end_time)->where('b.end_time', '>', $batch->start_time))
                ->select('aa.user_id', 'a.id', 'a.title')
                ->get()
                ->keyBy('user_id');
        }

        return User::select('id', 'name')->get()->map(function ($user) use ($assignedUserIds, $conflictsByUser) {
            $conflict = $conflictsByUser->get($user->id);

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'assigned' => in_array($user->id, $assignedUserIds),
                'has_time_conflict' => (bool) $conflict,
                'conflicting_assessment_id' => $conflict?->id,
                'conflicting_assessment_title' => $conflict?->title,
            ];
        })->all();
    }

    public function assign(Admin $admin, array $data): array
    {
        $validated = Validator::make($data, [
            'assessment_id' => 'required|integer|exists:assessments,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'batch_id' => 'nullable|integer|exists:batches,id',
        ])->validate();

        $assessment = $this->ownedAssessment($admin, $validated['assessment_id']);

        $result = resolve_batch($assessment, $validated['batch_id'] ?? null);

        if (isset($result['error'])) {
            abort(422, $result['error']->getData()->message);
        }

        $batch = $result['batch'];

        if ($this->isBatchLocked($batch->id)) {
            abort(403, 'Cannot assign users after batch has been attempted');
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
                    ->where(fn ($q) => $q->where('b.start_time', '<', $batch->end_time)->where('b.end_time', '>', $batch->start_time))
                    ->select('a.id', 'a.title')
                    ->first();
            }

            if ($conflict) {
                $conflicts[] = ['user_id' => $userId, 'conflicting_assessment_id' => $conflict->id, 'conflicting_assessment_title' => $conflict->title];
                continue;
            }

            $assignment = AssessmentAssignment::withTrashed()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $userId)
                ->where('batch_id', $batch->id)
                ->first();

            if ($assignment) {
                $assignment->restore();
            } else {
                try {
                    $assignment = AssessmentAssignment::create([
                        'assessment_id' => $assessment->id,
                        'user_id' => $userId,
                        'batch_id' => $batch->id,
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
                ['assessment_id' => $assessment->id, 'admin_id' => $admin->id]
            );

            $assignments[] = $assignment;
        }

        $hideBatch = is_implicit_batch($assessment, $batch);

        return [
            'assigned' => collect($assignments)->map(fn ($assignment) => [
                ...$assignment->toArray(),
                'batch_id' => $hideBatch ? null : $assignment->batch_id,
            ])->all(),
            'blocked_due_to_conflict' => $conflicts,
        ];
    }

    public function unassign(Admin $admin, array $data): int
    {
        $validated = Validator::make($data, [
            'assessment_id' => 'required|integer|exists:assessments,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'batch_id' => 'nullable|integer|exists:batches,id',
        ])->validate();

        $assessment = $this->ownedAssessment($admin, $validated['assessment_id']);

        $result = resolve_batch($assessment, $validated['batch_id'] ?? null);

        if (isset($result['error'])) {
            abort(422, $result['error']->getData()->message);
        }

        $batch = $result['batch'];

        if ($this->isBatchLocked($batch->id)) {
            abort(403, 'Cannot unassign users after batch has been attempted');
        }

        return AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('batch_id', $batch->id)
            ->whereIn('user_id', $validated['user_ids'])
            ->delete();
    }
}
