<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserAssessmentController extends Controller
{
    // Helper to preload batches
    private function getBatchesMap($assignments)
    {
        $batchIds = $assignments->pluck('batch_id')->filter()->unique();

        return Batch::whereIn('id', $batchIds)->get()->keyBy('id');
    }

    // Helper to look up one assignment's batch in the preloaded map.
    // Not to be confused with the global resolve_batch() helper, which
    // resolves which batch a request applies to.
    private function batchFor($assignment, $batches)
    {
        return $batches[$assignment->batch_id] ?? null;
    }

    private function mapWithBatch($assignments, $batches)
    {
        return $assignments->map(function ($assignment) use ($batches) {

            $assessment = $assignment->assessment;
            $batch = $this->batchFor($assignment, $batches);

            return [
                ...$assessment->toArray(),

                'batch_id' => $batch?->id,

                'publish_date' => $batch?->publish_date,
                'start_time' => $batch?->start_time,
                'end_time' => $batch?->end_time,

                'expiry_date' => $batch?->expiry_date,
                'duration_minutes' => $batch?->duration_minutes,
            ];
        })->values();
    }

    public function upcoming(Request $request)
    {
        $user = $request->user('users');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assignments = AssessmentAssignment::where('user_id', $user->id)
            ->with('assessment')
            ->get();

        $batches = $this->getBatchesMap($assignments);

        $filtered = $assignments->filter(function ($assignment) use ($today, $nowTime, $batches) {

            $assessment = $assignment->assessment;

            if ($assessment->is_flexible) {
                return false;
            }

            if (!$assessment->is_active)
                return false;

            $batch = $this->batchFor($assignment, $batches);

            if (!$batch)
                return false;

            return (
                $batch->publish_date > $today
            ) || (
                $batch->publish_date == $today &&
                $batch->start_time > $nowTime
            );
        });

        return response()->json($this->mapWithBatch($filtered, $batches));
    }

    public function today(Request $request)
    {
        $user = $request->user('users');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assignments = AssessmentAssignment::where('user_id', $user->id)
            ->with('assessment')
            ->get();

        $batches = $this->getBatchesMap($assignments);

        $filtered = $assignments->filter(function ($assignment) use ($today, $nowTime, $batches) {

            $assessment = $assignment->assessment;

            if ($assessment->is_flexible) {
                return false;
            }

            if (!$assessment->is_active)
                return false;

            $batch = $this->batchFor($assignment, $batches);

            if (!$batch)
                return false;

            return (
                $batch->publish_date == $today &&
                $batch->start_time > $nowTime
            );
        });

        return response()->json($this->mapWithBatch($filtered, $batches));
    }

    public function running(Request $request)
    {
        $user = $request->user('users');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assignments = AssessmentAssignment::where('user_id', $user->id)
            ->with('assessment')
            ->get();

        $batches = $this->getBatchesMap($assignments);

        $filtered = $assignments->filter(function ($assignment) use ($today, $nowTime, $user, $batches) {

            $assessment = $assignment->assessment;

            if (!$assessment->is_active)
                return false;

            $batch = $this->batchFor($assignment, $batches);

            if (!$batch)
                return false;

            if ($assessment->is_flexible) {
                $isRunning =
                    !$batch->expiry_date ||
                    $batch->expiry_date >= $today;

            } else {
                $isRunning =
                    $batch->publish_date == $today &&
                    $batch->start_time <= $nowTime &&
                    $batch->end_time >= $nowTime;

            }

            if (!$isRunning)
                return false;

            return !DB::table('assessment_attempts')
                ->where('assessment_id', $assessment->id)
                ->where('batch_id', $assignment->batch_id)
                ->where('user_id', $user->id)
                ->whereNotNull('submitted_at')
                ->exists();
        });

        return response()->json($this->mapWithBatch($filtered, $batches));
    }

    public function completed(Request $request)
    {
        $user = $request->user('users');

        $attempts = DB::table('assessment_attempts')
            ->where('user_id', $user->id)
            ->select('assessment_id', 'batch_id')
            ->get();

        $assignments = AssessmentAssignment::where('user_id', $user->id)
            ->with('assessment')
            ->get();

        $batches = $this->getBatchesMap($assignments);

        $filtered = $assignments->filter(function ($assignment) use ($attempts) {
            return $attempts->contains(function ($a) use ($assignment) {
                return $a->assessment_id == $assignment->assessment_id
                    && $a->batch_id == $assignment->batch_id;
            });
        });

        return response()->json($this->mapWithBatch($filtered, $batches));
    }

    public function missed(Request $request)
    {
        $user = $request->user('users');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $attempts = DB::table('assessment_attempts')
            ->where('user_id', $user->id)
            ->select('assessment_id', 'batch_id')
            ->get();

        $assignments = AssessmentAssignment::where('user_id', $user->id)
            ->with('assessment')
            ->get();

        $batches = $this->getBatchesMap($assignments);

        $filtered = $assignments->filter(function ($assignment) use ($attempts, $today, $nowTime, $batches) {

            $batch = $this->batchFor($assignment, $batches);

            if (!$assignment->assessment->is_active) {
                return false;
            }

            if ($assignment->assessment->is_flexible) {
                return false;
            }

            if (!$batch)
                return false;

            $isMissed =
                ($batch->publish_date < $today) ||
                ($batch->publish_date == $today && $batch->end_time < $nowTime);

            if (!$isMissed)
                return false;

            return !$attempts->contains(function ($a) use ($assignment) {
                return $a->assessment_id == $assignment->assessment_id
                    && $a->batch_id == $assignment->batch_id;
            });
        });

        return response()->json($this->mapWithBatch($filtered, $batches));
    }

    public function show(Request $request, $id)
    {
        $user = $request->user('users');

        $assignment = AssessmentAssignment::where('user_id', $user->id)
            ->where('assessment_id', $id)
            ->with('assessment')
            ->latest('id')
            ->firstOrFail();

        $batches = $this->getBatchesMap(collect([$assignment]));

        return response()->json(
            $this->mapWithBatch(collect([$assignment]), $batches)->first()
        );
    }
}