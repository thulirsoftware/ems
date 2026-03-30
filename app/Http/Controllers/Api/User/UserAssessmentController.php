<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use App\Models\Batch;
use Illuminate\Http\Request;

class UserAssessmentController extends Controller
{
    // Helper to preload batches
    private function getBatchesMap($assignments)
    {
        $batchIds = $assignments->pluck('batch_id')->filter()->unique();

        return Batch::whereIn('id', $batchIds)->get()->keyBy('id');
    }

    private function resolveBatch($assignment, $batches)
    {
        return $batches[$assignment->batch_id] ?? null;
    }

    private function mapWithBatch($assignments, $batches)
    {
        return $assignments->map(function ($assignment) use ($batches) {

            $assessment = $assignment->assessment;
            $batch = $this->resolveBatch($assignment, $batches);

            return [
                ...$assessment->toArray(),
                'batch_id' => $batch?->id,
                'publish_date' => $batch?->publish_date,
                'start_time' => $batch?->start_time,
                'end_time' => $batch?->end_time,
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

            if (!$assessment->is_active)
                return false;

            $batch = $this->resolveBatch($assignment, $batches);

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

            if (!$assessment->is_active)
                return false;

            $batch = $this->resolveBatch($assignment, $batches);

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

            $batch = $this->resolveBatch($assignment, $batches);

            if (!$batch)
                return false;

            $isRunning =
                $batch->publish_date == $today &&
                $batch->start_time <= $nowTime &&
                $batch->end_time >= $nowTime;

            if (!$isRunning)
                return false;

            return !\DB::table('assessment_attempts')
                ->where('assessment_id', $assessment->id)
                ->where('batch_id', $assignment->batch_id) // ✅ FIX
                ->where('user_id', $user->id)
                ->whereNotNull('submitted_at')
                ->exists();
        });

        return response()->json($this->mapWithBatch($filtered, $batches));
    }

    public function completed(Request $request)
    {
        $user = $request->user('users');

        $attempts = \DB::table('assessment_attempts')
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

        $attempts = \DB::table('assessment_attempts')
            ->where('user_id', $user->id)
            ->select('assessment_id', 'batch_id')
            ->get();

        $assignments = AssessmentAssignment::where('user_id', $user->id)
            ->with('assessment')
            ->get();

        $batches = $this->getBatchesMap($assignments);

        $filtered = $assignments->filter(function ($assignment) use ($attempts, $today, $nowTime, $batches) {

            $batch = $this->resolveBatch($assignment, $batches);

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