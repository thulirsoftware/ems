<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class BatchService
{
    public function __construct(
        private AssignmentService $assignmentService
    ) {}

    private function isLocked(int $batchId): bool
    {
        return AssessmentAttempt::where('batch_id', $batchId)->exists();
    }

    // $id is untyped — see the comment on AssessmentService::findOwned() for why.
    private function findManageable(Admin $admin, $id): Batch
    {
        return Batch::batchWiseOnly()
            ->where('id', $id)
            ->whereHas('assessment', fn ($q) => $q->where('admin_id', $admin->id))
            ->firstOrFail();
    }

    public function list(Admin $admin)
    {
        return Batch::batchWiseOnly()
            ->whereHas('assessment', fn ($q) => $q->where('admin_id', $admin->id))
            ->get();
    }

    public function listByAssessment(Admin $admin, $assessmentId)
    {
        $assessment = Assessment::where('id', $assessmentId)->where('admin_id', $admin->id)->firstOrFail();

        if ($assessment->scheduling_type !== Assessment::SCHEDULING_BATCH_WISE) {
            abort(422, 'Batches are only managed via this endpoint for batch_wise assessments');
        }

        return Batch::where('assessment_id', $assessment->id)->get();
    }

    public function get(Admin $admin, $id): Batch
    {
        return $this->findManageable($admin, $id);
    }

    public function create(Admin $admin, array $data): Batch
    {
        $validated = Validator::make($data, [
            'name' => 'required|string|max:255',
            'publish_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'assessment_id' => 'required|exists:assessments,id',
            'capacity' => 'required|integer|min:1',
        ])->validate();

        $assessment = Assessment::where('id', $validated['assessment_id'])->where('admin_id', $admin->id)->firstOrFail();

        if ($assessment->scheduling_type !== Assessment::SCHEDULING_BATCH_WISE) {
            abort(422, 'Batches can only be created for batch_wise assessments');
        }

        $conflict = Batch::where('assessment_id', $assessment->id)
            ->whereDate('publish_date', $validated['publish_date'])
            ->where(fn ($q) => $q->where('start_time', '<', $validated['end_time'])->where('end_time', '>', $validated['start_time']))
            ->exists();

        if ($conflict) {
            abort(422, 'Batch time conflict');
        }

        return Batch::create($validated);
    }

    public function update(Admin $admin, $id, array $data): Batch
    {
        $batch = $this->findManageable($admin, $id);

        if ($this->isLocked($batch->id)) {
            abort(403, 'Cannot modify batch after it has been attempted');
        }

        $validated = Validator::make($data, [
            'name' => 'sometimes|required|string|max:255',
            'publish_date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',
            'capacity' => 'sometimes|required|integer|min:1',
        ])->validate();

        $start = $validated['start_time'] ?? $batch->start_time;
        $end = $validated['end_time'] ?? $batch->end_time;
        $date = $validated['publish_date'] ?? $batch->publish_date;

        if ($start && $end && $end <= $start) {
            abort(422, 'end_time must be after start_time');
        }

        $conflict = Batch::where('assessment_id', $batch->assessment_id)
            ->whereDate('publish_date', $date)
            ->where('id', '!=', $batch->id)
            ->where(fn ($q) => $q->where('start_time', '<', $end)->where('end_time', '>', $start))
            ->exists();

        if ($conflict) {
            abort(422, 'Batch time conflict');
        }

        $batch->update($validated);

        return $batch;
    }

    public function delete(Admin $admin, $id): void
    {
        $batch = $this->findManageable($admin, $id);

        if ($this->isLocked($batch->id)) {
            abort(403, 'Cannot delete batch after it has been attempted');
        }

        $batch->delete();
    }

    public function listUsers(Admin $admin, $id)
    {
        $batch = $this->findManageable($admin, $id);

        return User::whereIn('id', AssessmentAssignment::where('batch_id', $batch->id)->pluck('user_id'))->get();
    }

    public function addUsers(Admin $admin, $id, array $data): array
    {
        $batch = $this->findManageable($admin, $id);

        if ($this->isLocked($batch->id)) {
            abort(403, 'Cannot add users after batch has started');
        }

        $validated = Validator::make($data, [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ])->validate();

        $currentUsers = AssessmentAssignment::where('batch_id', $batch->id)->pluck('user_id')->toArray();
        $newUsers = array_diff($validated['user_ids'], $currentUsers);

        if ($batch->capacity && (count($currentUsers) + count($newUsers)) > $batch->capacity) {
            abort(422, 'Capacity exceeded');
        }

        return $this->assignmentService->assign($admin, [
            'assessment_id' => $batch->assessment_id,
            'user_ids' => $validated['user_ids'],
            'batch_id' => $batch->id,
        ]);
    }

    public function removeUsers(Admin $admin, $id, array $data): int
    {
        $batch = $this->findManageable($admin, $id);

        $validated = Validator::make($data, [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ])->validate();

        return $this->assignmentService->unassign($admin, [
            'assessment_id' => $batch->assessment_id,
            'user_ids' => $validated['user_ids'],
            'batch_id' => $batch->id,
        ]);
    }
}
