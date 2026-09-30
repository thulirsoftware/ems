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

        // fixed/flexible → an explicit batch_id targets a specific batch (e.g. a
        // re-exam batch); otherwise use the assessment's own implicit batch.
        // Never "the latest batch": once a re-exam exists that would silently
        // redirect assignments and results away from the original exam.
        if ($batchId) {
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
            ->where('name', 'individual_batch_' . $assessment->id)
            ->first()
            ?? Batch::where('assessment_id', $assessment->id)->oldest('id')->first();

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

if (!function_exists('attempt_answer_deadline')) {
    // The moment an attempt stops accepting answers — the single source of
    // truth for "can this student still answer?". Fixed/batch_wise attempts
    // close at their batch's end time; flexible ones at
    // flexible_attempt_deadline(). Null when the batch has no schedule, which
    // callers must treat as closed.
    function attempt_answer_deadline($assessment, $batch, $attempt)
    {
        if (!$batch || !$attempt) {
            return null;
        }

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
            return flexible_attempt_deadline($attempt, $batch);
        }

        if (!$batch->publish_date || !$batch->end_time) {
            return null;
        }

        return Carbon::parse($batch->publish_date . ' ' . $batch->end_time, config('app.timezone'));
    }
}

if (!function_exists('attempt_answering_open')) {
    function attempt_answering_open($assessment, $batch, $attempt): bool
    {
        $deadline = attempt_answer_deadline($assessment, $batch, $attempt);

        return $deadline !== null && app_now()->lte($deadline);
    }
}

if (!function_exists('results_release_time')) {
    // When per-question results (correct answers, right/wrong) may be shown to
    // students: only once the batch's window has closed for everyone, so an
    // early finisher can't pass answers to candidates still writing. Null when
    // the batch has no closing time (results are released immediately).
    function results_release_time($assessment, $batch)
    {
        if (!$batch) {
            return null;
        }

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
            return $batch->expiry_date
                ? Carbon::parse($batch->expiry_date, config('app.timezone'))->endOfDay()
                : null;
        }

        if (!$batch->publish_date || !$batch->end_time) {
            return null;
        }

        return Carbon::parse($batch->publish_date . ' ' . $batch->end_time, config('app.timezone'));
    }
}

if (!function_exists('results_released')) {
    function results_released($assessment, $batch): bool
    {
        $releaseAt = results_release_time($assessment, $batch);

        return $releaseAt === null || app_now()->gt($releaseAt);
    }
}

if (!function_exists('attempt_state')) {
    // What the student UI needs to know about an attempt: whether it exists /
    // is submitted, whether answering is still open, and the seconds left
    // (computed server-side so the countdown is right on any device/clock).
    function attempt_state($assessment, $batch, $attempt): array
    {
        if (!$attempt) {
            return ['attempt_status' => 'not_started', 'answering_closed' => false, 'seconds_remaining' => null];
        }

        if ($attempt->submitted_at) {
            return ['attempt_status' => 'submitted', 'answering_closed' => true, 'seconds_remaining' => 0];
        }

        $deadline = attempt_answer_deadline($assessment, $batch, $attempt);
        $open = $deadline !== null && app_now()->lte($deadline);

        return [
            'attempt_status' => 'in_progress',
            'answering_closed' => !$open,
            'seconds_remaining' => $open ? (int) app_now()->diffInSeconds($deadline, true) : 0,
        ];
    }
}