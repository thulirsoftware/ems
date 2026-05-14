<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAttempt;
use Illuminate\Http\Request;
use App\Models\Batch;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;

class ReExamController extends Controller
{
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

        if ($assessment->is_flexible) {
            return response()->json([
                'message' => 'Flexible assessments do not support re-exams'
            ], 422);
        }

        $sourceBatch = null;

        // 🔥 Resolve users + source batch
        if ($validated['use_previous_users']) {

            if ($assessment->is_batch_wise) {

                if (empty($validated['source_batch_id'])) {
                    return response()->json([
                        'message' => 'source_batch_id is required for batch-wise assessments'
                    ], 422);
                }

                $sourceBatch = Batch::find($validated['source_batch_id']);

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

            // ⚠️ For manual mode, source batch is optional
            if (!empty($validated['source_batch_id'])) {
                $sourceBatch = Batch::find($validated['source_batch_id']);
            }
        }

        if (empty($userIds)) {
            return response()->json([
                'message' => 'No users found to assign'
            ], 422);
        }

        // 🔥 Prevent creating re-exam before source batch ends
        if ($sourceBatch) {

            $today = app_now()->toDateString();
            $nowTime = app_now()->toTimeString();

            $isStillRunning =
                ($sourceBatch->publish_date > $today) ||
                ($sourceBatch->publish_date == $today && $sourceBatch->end_time > $nowTime);

            if ($isStillRunning) {
                return response()->json([
                    'message' => 'Cannot create re-exam before current batch is completed'
                ], 422);
            }
        }

        // 🔥 Copy capacity (if exists)
        $capacity = $sourceBatch?->capacity ?? null;

        // 🔥 Create new batch
        $batch = Batch::create([
            'assessment_id' => $assessment->id,
            'name' => 're_exam_batch_' . $assessment->id . '_' . time(),
            'publish_date' => $validated['publish_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'capacity' => $capacity, // ✅ copied
        ]);

        $assignments = [];

        foreach ($userIds as $userId) {

            $assignment = AssessmentAssignment::create([
                'assessment_id' => $assessment->id,
                'user_id' => $userId,
                'batch_id' => $batch->id,
            ]);

            $assignments[] = $assignment;

            \App\Services\NotificationService::notifyUser(
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

        return response()->json([
            'message' => 'Re-exam created successfully',
            'batch' => $batch,
            'assigned_users_count' => count($assignments),
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

        // ✅ Require passing_percentage only when needed
        if (in_array($validated['filter'], ['failed', 'both']) && !isset($validated['passing_percentage'])) {
            return response()->json([
                'message' => 'passing_percentage is required for failed or both filter'
            ], 422);
        }

        $passingPercentage = $validated['passing_percentage'] ?? null;

        $assessment = Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        if ($assessment->is_flexible) {
            return response()->json([
                'message' => 'Flexible assessments do not support re-exams'
            ], 422);
        }

        // ✅ Resolve source batch
        if ($assessment->is_batch_wise) {

            if (empty($validated['source_batch_id'])) {
                return response()->json([
                    'message' => 'source_batch_id is required for batch-wise assessments'
                ], 422);
            }

            $sourceBatch = Batch::findOrFail($validated['source_batch_id']);

        } else {

            $sourceBatch = Batch::where('assessment_id', $assessment->id)
                ->where('name', 'individual_batch_' . $assessment->id)
                ->firstOrFail();
        }

        // ✅ Prevent re-exam before batch ends
        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $isStillRunning =
            ($sourceBatch->publish_date > $today) ||
            ($sourceBatch->publish_date == $today && $sourceBatch->end_time > $nowTime);

        if ($isStillRunning) {
            return response()->json([
                'message' => 'Cannot create re-exam before current batch is completed'
            ], 422);
        }

        // ✅ Assigned users
        $assignedUserIds = AssessmentAssignment::where('batch_id', $sourceBatch->id)
            ->pluck('user_id');

        // ✅ Attempts map
        $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('batch_id', $sourceBatch->id)
            ->get()
            ->keyBy('user_id');

        $userIds = [];

        foreach ($assignedUserIds as $userId) {

            $attempt = $attempts->get($userId);

            // 🔥 ABSENT (no attempt OR not submitted)
            if (!$attempt || !$attempt->submitted_at) {

                if (in_array($validated['filter'], ['absent', 'both'])) {
                    $userIds[] = $userId;
                }

                continue; // ✅ critical to avoid double processing
            }

            // 🔥 FAILED
            if (in_array($validated['filter'], ['failed', 'both'])) {

                $scoreParts = explode('/', $attempt->score);
                $scoreValue = (int) ($scoreParts[0] ?? 0);
                $total = (int) ($scoreParts[1] ?? 1);

                $percentage = ($total > 0) ? ($scoreValue / $total) * 100 : 0;

                if ($percentage < $passingPercentage) {
                    $userIds[] = $userId;
                }
            }
        }

        // ✅ Remove duplicates (extra safety)
        $userIds = array_values(array_unique($userIds));

        if (empty($userIds)) {
            return response()->json([
                'message' => 'No users matched the selected filter'
            ], 422);
        }

        // ✅ Create new batch (copy capacity)
        $batch = Batch::create([
            'assessment_id' => $assessment->id,
            'name' => 're_exam_filtered_' . $assessment->id . '_' . time(),
            'publish_date' => $validated['publish_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'capacity' => $sourceBatch->capacity ?? null,
        ]);

        foreach ($userIds as $userId) {

            AssessmentAssignment::create([
                'assessment_id' => $assessment->id,
                'user_id' => $userId,
                'batch_id' => $batch->id,
            ]);

            \App\Services\NotificationService::notifyUser(
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

        return response()->json([
            'message' => 'Filtered re-exam created successfully',
            'batch' => $batch,
            'assigned_users_count' => count($userIds),
        ], 201);
    }
}