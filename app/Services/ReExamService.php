<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\Batch;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReExamService
{
    private function isSourceBatchStillRunning($sourceBatch): bool
    {
        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        return ($sourceBatch->publish_date > $today) || ($sourceBatch->publish_date == $today && $sourceBatch->end_time > $nowTime);
    }

    private function createReExamBatch(Admin $admin, Assessment $assessment, array $validated, string $namePrefix, ?int $capacity, array $userIds): Batch
    {
        $userIds = array_values(array_unique($userIds));

        return DB::transaction(function () use ($admin, $assessment, $validated, $namePrefix, $capacity, $userIds) {
            $batch = Batch::create([
                'assessment_id' => $assessment->id,
                'name' => $namePrefix.$assessment->id.'_'.time(),
                'publish_date' => $validated['publish_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'capacity' => $capacity,
            ]);

            foreach ($userIds as $userId) {
                AssessmentAssignment::create(['assessment_id' => $assessment->id, 'user_id' => $userId, 'batch_id' => $batch->id]);

                NotificationService::notifyUser(
                    $userId,
                    're_exam_assigned',
                    'Re-exam scheduled',
                    "You have a re-exam for {$assessment->title}",
                    ['assessment_id' => $assessment->id, 'batch_id' => $batch->id]
                );
            }

            return $batch;
        });
    }

    public function create(Admin $admin, array $data): array
    {
        $validated = Validator::make($data, [
            'assessment_id' => 'required|exists:assessments,id',
            'use_previous_users' => 'required|boolean',
            'source_batch_id' => 'nullable|exists:batches,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'publish_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ])->validate();

        $assessment = Assessment::where('id', $validated['assessment_id'])->where('admin_id', $admin->id)->firstOrFail();

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
            abort(422, 'Flexible assessments do not support re-exams');
        }

        $sourceBatch = null;

        if ($validated['use_previous_users']) {
            if ($assessment->scheduling_type === Assessment::SCHEDULING_BATCH_WISE) {
                if (empty($validated['source_batch_id'])) {
                    abort(422, 'source_batch_id is required for batch-wise assessments');
                }

                $sourceBatch = Batch::where('id', $validated['source_batch_id'])->where('assessment_id', $assessment->id)->firstOrFail();
            } else {
                $sourceBatch = Batch::where('assessment_id', $assessment->id)->where('name', 'individual_batch_'.$assessment->id)->first();

                if (!$sourceBatch) {
                    abort(422, 'Individual batch not found');
                }
            }

            $userIds = AssessmentAssignment::where('batch_id', $sourceBatch->id)->pluck('user_id')->toArray();
        } else {
            if (empty($validated['user_ids'])) {
                abort(422, 'user_ids are required when use_previous_users is false');
            }

            $userIds = $validated['user_ids'];

            if (!empty($validated['source_batch_id'])) {
                $sourceBatch = Batch::where('id', $validated['source_batch_id'])->where('assessment_id', $assessment->id)->firstOrFail();
            }
        }

        if (empty($userIds)) {
            abort(422, 'No users found to assign');
        }

        if ($sourceBatch && $this->isSourceBatchStillRunning($sourceBatch)) {
            abort(422, 'Cannot create re-exam before current batch is completed');
        }

        $batch = $this->createReExamBatch($admin, $assessment, $validated, 're_exam_batch_', $sourceBatch?->capacity, $userIds);

        return ['batch' => $batch, 'assigned_users_count' => count($userIds)];
    }

    public function createFiltered(Admin $admin, array $data): array
    {
        $validated = Validator::make($data, [
            'assessment_id' => 'required|exists:assessments,id',
            'filter' => 'required|in:failed,absent,both',
            'passing_percentage' => 'nullable|numeric|min:0|max:100',
            'source_batch_id' => 'nullable|exists:batches,id',
            'publish_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ])->validate();

        if (in_array($validated['filter'], ['failed', 'both']) && !isset($validated['passing_percentage'])) {
            abort(422, 'passing_percentage is required for failed or both filter');
        }

        $passingPercentage = $validated['passing_percentage'] ?? null;

        $assessment = Assessment::where('id', $validated['assessment_id'])->where('admin_id', $admin->id)->firstOrFail();

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
            abort(422, 'Flexible assessments do not support re-exams');
        }

        if ($assessment->scheduling_type === Assessment::SCHEDULING_BATCH_WISE) {
            if (empty($validated['source_batch_id'])) {
                abort(422, 'source_batch_id is required for batch-wise assessments');
            }

            $sourceBatch = Batch::where('id', $validated['source_batch_id'])->where('assessment_id', $assessment->id)->firstOrFail();
        } else {
            $sourceBatch = Batch::where('assessment_id', $assessment->id)->where('name', 'individual_batch_'.$assessment->id)->firstOrFail();
        }

        if ($this->isSourceBatchStillRunning($sourceBatch)) {
            abort(422, 'Cannot create re-exam before current batch is completed');
        }

        $assignedUserIds = AssessmentAssignment::where('batch_id', $sourceBatch->id)->pluck('user_id');
        $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)->where('batch_id', $sourceBatch->id)->get()->keyBy('user_id');

        $userIds = [];

        foreach ($assignedUserIds as $userId) {
            $attempt = $attempts->get($userId);

            if (!$attempt || !$attempt->submitted_at) {
                if (in_array($validated['filter'], ['absent', 'both'])) {
                    $userIds[] = $userId;
                }

                continue;
            }

            if (in_array($validated['filter'], ['failed', 'both'])) {
                $result = parse_score($attempt->score);

                if ($result && $result['percentage'] < $passingPercentage) {
                    $userIds[] = $userId;
                }
            }
        }

        $userIds = array_values(array_unique($userIds));

        if (empty($userIds)) {
            abort(422, 'No users matched the selected filter');
        }

        $batch = $this->createReExamBatch($admin, $assessment, $validated, 're_exam_filtered_', $sourceBatch->capacity, $userIds);

        return ['batch' => $batch, 'assigned_users_count' => count($userIds)];
    }
}
