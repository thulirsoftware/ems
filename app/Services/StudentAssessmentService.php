<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentAssessmentService
{
    private function batchesMap($assignments)
    {
        return Batch::whereIn('id', $assignments->pluck('batch_id')->filter()->unique())->get()->keyBy('id');
    }

    private function mapWithBatch($assignments, $batches)
    {
        return $assignments->map(function ($assignment) use ($batches) {
            $assessment = $assignment->assessment;
            $batch = $batches[$assignment->batch_id] ?? null;

            return [...$assessment->toArray(), ...batch_schedule_fields($assessment, $batch)];
        })->values();
    }

    private function assignments(User $user)
    {
        return AssessmentAssignment::where('user_id', $user->id)->with('assessment')->get();
    }

    public function upcoming(User $user): array
    {
        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assignments = $this->assignments($user);
        $batches = $this->batchesMap($assignments);

        $filtered = $assignments->filter(function ($assignment) use ($today, $nowTime, $batches) {
            $assessment = $assignment->assessment;

            if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE || !$assessment->is_active) {
                return false;
            }

            $batch = $batches[$assignment->batch_id] ?? null;

            if (!$batch) {
                return false;
            }

            return $batch->publish_date > $today || ($batch->publish_date == $today && $batch->start_time > $nowTime);
        });

        return $this->mapWithBatch($filtered, $batches)->all();
    }

    public function today(User $user): array
    {
        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assignments = $this->assignments($user);
        $batches = $this->batchesMap($assignments);

        $filtered = $assignments->filter(function ($assignment) use ($today, $nowTime, $batches) {
            $assessment = $assignment->assessment;

            if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE || !$assessment->is_active) {
                return false;
            }

            $batch = $batches[$assignment->batch_id] ?? null;

            return $batch && $batch->publish_date == $today && $batch->start_time > $nowTime;
        });

        return $this->mapWithBatch($filtered, $batches)->all();
    }

    public function running(User $user): array
    {
        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assignments = $this->assignments($user);
        $batches = $this->batchesMap($assignments);

        $filtered = $assignments->filter(function ($assignment) use ($today, $nowTime, $batches, $user) {
            $assessment = $assignment->assessment;

            if (!$assessment->is_active) {
                return false;
            }

            $batch = $batches[$assignment->batch_id] ?? null;

            if (!$batch) {
                return false;
            }

            $isRunning = $assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE
                ? (!$batch->publish_date || $batch->publish_date <= $today) && (!$batch->expiry_date || $batch->expiry_date >= $today)
                : $batch->publish_date == $today && $batch->start_time <= $nowTime && $batch->end_time >= $nowTime;

            if (!$isRunning) {
                return false;
            }

            return !DB::table('assessment_attempts')
                ->where('assessment_id', $assessment->id)
                ->where('batch_id', $assignment->batch_id)
                ->where('user_id', $user->id)
                ->whereNotNull('submitted_at')
                ->exists();
        });

        return $this->mapWithBatch($filtered, $batches)->all();
    }

    public function completed(User $user): array
    {
        $attempts = DB::table('assessment_attempts')->where('user_id', $user->id)->select('assessment_id', 'batch_id')->get();

        $assignments = $this->assignments($user);
        $batches = $this->batchesMap($assignments);

        $filtered = $assignments->filter(fn ($assignment) => $attempts->contains(
            fn ($a) => $a->assessment_id == $assignment->assessment_id && $a->batch_id == $assignment->batch_id
        ));

        return $this->mapWithBatch($filtered, $batches)->all();
    }

    public function missed(User $user): array
    {
        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $attempts = DB::table('assessment_attempts')->where('user_id', $user->id)->select('assessment_id', 'batch_id')->get();

        $assignments = $this->assignments($user);
        $batches = $this->batchesMap($assignments);

        $filtered = $assignments->filter(function ($assignment) use ($attempts, $today, $nowTime, $batches) {
            $batch = $batches[$assignment->batch_id] ?? null;

            if (!$assignment->assessment->is_active || $assignment->assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE || !$batch) {
                return false;
            }

            $isMissed = ($batch->publish_date < $today) || ($batch->publish_date == $today && $batch->end_time < $nowTime);

            if (!$isMissed) {
                return false;
            }

            return !$attempts->contains(fn ($a) => $a->assessment_id == $assignment->assessment_id && $a->batch_id == $assignment->batch_id);
        });

        return $this->mapWithBatch($filtered, $batches)->all();
    }

    // $assessmentId is untyped — see the comment on
    // AssessmentService::findOwned() for why.
    public function get(User $user, $assessmentId): array
    {
        $assignment = AssessmentAssignment::where('user_id', $user->id)
            ->where('assessment_id', $assessmentId)
            ->with('assessment')
            ->latest('id')
            ->firstOrFail();

        $batches = $this->batchesMap(collect([$assignment]));

        return $this->mapWithBatch(collect([$assignment]), $batches)->first();
    }
}
