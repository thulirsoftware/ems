<?php

use Carbon\Carbon;
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
        if ($assessment->is_batch_wise) {

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

        // individual → get default batch
        $batch = Batch::where('assessment_id', $assessment->id)
            ->where('name', 'individual_batch_' . $assessment->id)
            ->first();

        return ['batch' => $batch];
    }
}