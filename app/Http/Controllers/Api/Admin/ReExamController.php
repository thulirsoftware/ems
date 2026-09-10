<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAttempt;
use Illuminate\Http\Request;
use App\Models\Batch;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class ReExamController extends Controller
{
    // A re-exam may only be scheduled once the source batch has finished
    private function isSourceBatchStillRunning($sourceBatch)
    {
        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        return ($sourceBatch->publish_date > $today) ||
            ($sourceBatch->publish_date == $today && $sourceBatch->end_time > $nowTime);
    }

    // Create the re-exam batch, assign every user to it and notify them.
    // Wrapped in a transaction so a failure partway through (e.g. one bad
    // notification write) can't leave a batch with only some users assigned.
    private function createReExamBatch($assessment, $validated, $namePrefix, $capacity, $userIds)
    {
        // Callers may pass duplicate ids (e.g. a manually submitted user_ids
        // array); since assignments now carry a unique (assessment, user,
        // batch) constraint, de-duplicating here — once — keeps every caller
        // safe instead of relying on each one to remember to do it.
        $userIds = array_values(array_unique($userIds));

        return DB::transaction(function () use ($assessment, $validated, $namePrefix, $capacity, $userIds) {

            $batch = Batch::create([
                'assessment_id' => $assessment->id,
                'name' => $namePrefix . $assessment->id . '_' . time(),
                'publish_date' => $validated['publish_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'capacity' => $capacity,
            ]);

            foreach ($userIds as $userId) {

                AssessmentAssignment::create([
                    'assessment_id' => $assessment->id,
                    'user_id' => $userId,
                    'batch_id' => $batch->id,
                ]);

                NotificationService::notifyUser(
                    $userId,
                    're_exam_assigned',
                    'Re-exam scheduled',
                    "You have a re-exam for {$assessment->title}",
                    [
                        'assessment_id' => $assessment->id,
                        'batch_id' => $batch->id
                    ]
                );
            }

            return $batch;
        });
    }

    public function createReExam(Request $request)
    {
        $admin = $request->user('admins');

        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'use_previous_users' => 'required|boolean',
            'source_batch_id' => 'nullable|exists:batches,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'publish_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        $assessment = Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
            return response()->json([
                'message' => 'Flexible assessments do not support re-exams'
            ], 422);
        }

        $sourceBatch = null;

        // Resolve users + source batch
        if ($validated['use_previous_users']) {

            if ($assessment->scheduling_type === Assessment::SCHEDULING_BATCH_WISE) {

                if (empty($validated['source_batch_id'])) {
                    return response()->json([
                        'message' => 'source_batch_id is required for batch-wise assessments'
                    ], 422);
                }

                $sourceBatch = Batch::where('id', $validated['source_batch_id'])
                    ->where('assessment_id', $assessment->id)
                    ->firstOrFail();

                $userIds = AssessmentAssignment::where('batch_id', $sourceBatch->id)
                    ->pluck('user_id')
                    ->toArray();

            } else {

                $sourceBatch = Batch::where('assessment_id', $assessment->id)
                    ->where('name', 'individual_batch_' . $assessment->id)
                    ->first();

                if (!$sourceBatch) {
                    return response()->json([
                        'message' => 'Individual batch not found'
                    ], 422);
                }

                $userIds = AssessmentAssignment::where('batch_id', $sourceBatch->id)
                    ->pluck('user_id')
                    ->toArray();
            }

        } else {

            if (empty($validated['user_ids'])) {
                return response()->json([
                    'message' => 'user_ids are required when use_previous_users is false'
                ], 422);
            }

            $userIds = $validated['user_ids'];

            // For manual mode, source batch is optional
            if (!empty($validated['source_batch_id'])) {
                $sourceBatch = Batch::where('id', $validated['source_batch_id'])
                    ->where('assessment_id', $assessment->id)
                    ->firstOrFail();
            }
        }

        if (empty($userIds)) {
            return response()->json([
                'message' => 'No users found to assign'
            ], 422);
        }

        // Prevent creating re-exam before source batch ends
        if ($sourceBatch && $this->isSourceBatchStillRunning($sourceBatch)) {
            return response()->json([
                'message' => 'Cannot create re-exam before current batch is completed'
            ], 422);
        }

        // Copy capacity (if exists)
        $capacity = $sourceBatch?->capacity ?? null;

        $batch = $this->createReExamBatch(
            $assessment,
            $validated,
            're_exam_batch_',
            $capacity,
            $userIds
        );

        return response()->json([
            'message' => 'Re-exam created successfully',
            'batch' => $batch,
            'assigned_users_count' => count($userIds),
        ], 201);
    }

    public function createFilteredReExam(Request $request)
    {
        $admin = $request->user('admins');

        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'filter' => 'required|in:failed,absent,both',
            'passing_percentage' => 'nullable|numeric|min:0|max:100',
            'source_batch_id' => 'nullable|exists:batches,id',
            'publish_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        // Require passing_percentage only when needed
        if (in_array($validated['filter'], ['failed', 'both']) && !isset($validated['passing_percentage'])) {
            return response()->json([
                'message' => 'passing_percentage is required for failed or both filter'
            ], 422);
        }

        $passingPercentage = $validated['passing_percentage'] ?? null;

        $assessment = Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
            return response()->json([
                'message' => 'Flexible assessments do not support re-exams'
            ], 422);
        }

        // Resolve source batch
        if ($assessment->scheduling_type === Assessment::SCHEDULING_BATCH_WISE) {

            if (empty($validated['source_batch_id'])) {
                return response()->json([
                    'message' => 'source_batch_id is required for batch-wise assessments'
                ], 422);
            }

            $sourceBatch = Batch::where('id', $validated['source_batch_id'])
                ->where('assessment_id', $assessment->id)
                ->firstOrFail();

        } else {

            $sourceBatch = Batch::where('assessment_id', $assessment->id)
                ->where('name', 'individual_batch_' . $assessment->id)
                ->firstOrFail();
        }

        // Prevent re-exam before batch ends
        if ($this->isSourceBatchStillRunning($sourceBatch)) {
            return response()->json([
                'message' => 'Cannot create re-exam before current batch is completed'
            ], 422);
        }

        // Assigned users
        $assignedUserIds = AssessmentAssignment::where('batch_id', $sourceBatch->id)
            ->pluck('user_id');

        // Attempts map
        $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('batch_id', $sourceBatch->id)
            ->get()
            ->keyBy('user_id');

        $userIds = [];

        foreach ($assignedUserIds as $userId) {

            $attempt = $attempts->get($userId);

            // ABSENT (no attempt OR not submitted)
            if (!$attempt || !$attempt->submitted_at) {

                if (in_array($validated['filter'], ['absent', 'both'])) {
                    $userIds[] = $userId;
                }

                continue; // avoid double processing
            }

            // FAILED — use the same score parsing as reports/dashboards so a
            // still-pending-evaluation attempt isn't silently treated as 0%.
            if (in_array($validated['filter'], ['failed', 'both'])) {

                $result = parse_score($attempt->score);

                if ($result && $result['percentage'] < $passingPercentage) {
                    $userIds[] = $userId;
                }
            }
        }

        // Remove duplicates (extra safety)
        $userIds = array_values(array_unique($userIds));

        if (empty($userIds)) {
            return response()->json([
                'message' => 'No users matched the selected filter'
            ], 422);
        }

        // Create new batch (copy capacity)
        $batch = $this->createReExamBatch(
            $assessment,
            $validated,
            're_exam_filtered_',
            $sourceBatch->capacity ?? null,
            $userIds
        );

        return response()->json([
            'message' => 'Filtered re-exam created successfully',
            'batch' => $batch,
            'assigned_users_count' => count($userIds),
        ], 201);
    }
}