<?php

use Carbon\Carbon;
use App\Models\Assessment;
use App\Models\Batch;

if (!function_exists('app_now')) {
    function app_now()
    {
        return Carbon::now(config('app.timezone'));
    }
}

if (!function_exists('resolve_batch')) {
    function resolve_batch($assessment, $batchId = null)
    {
        // batch-wise → require batch_id
        if ($assessment->scheduling_type === Assessment::SCHEDULING_BATCH_WISE) {

            if (!$batchId) {
                return [
                    'error' => response()->json([
                        'message' => 'batch_id is required for batch-wise assessments'
                    ], 422)
                ];
            }

            $batch = Batch::where('id', $batchId)
                ->where('assessment_id', $assessment->id)
                ->first();

            if (!$batch) {
                return [
                    'error' => response()->json([
                        'message' => 'Invalid batch_id for this assessment'
                    ], 422)
                ];
            }

            return ['batch' => $batch];
        }

        $batch = Batch::where('assessment_id', $assessment->id)
            ->latest('id')
            ->first();

        if (!$batch) {
            return [
                'error' => response()->json([
                    'message' => 'No batch exists for this assessment'
                ], 422)
            ];
        }

        return ['batch' => $batch];
    }
}

if (!function_exists('flexible_attempt_deadline')) {
    // The latest moment a flexible attempt may still be worked on: whichever
    // comes first between the per-attempt time limit (duration_minutes from
    // when it started) and the end of the assessment's allowed date range
    // (end_date), so an attempt started near end_date can't run past it.
    function flexible_attempt_deadline($attempt, $batch)
    {
        $deadline = $attempt->created_at->copy()->addMinutes($batch->duration_minutes);

        if (!$batch->expiry_date) {
            return $deadline;
        }

        $windowEnd = Carbon::parse($batch->expiry_date, config('app.timezone'))->endOfDay();

        return $deadline->lt($windowEnd) ? $deadline : $windowEnd;
    }
}