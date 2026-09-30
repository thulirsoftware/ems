<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentAssessmentService
{
    private function batchesMap($assignments)
    {
        return Batch::whereIn('id', $assignments->pluck('batch_id')->filter()->unique())->get()->keyBy('id');
    }

    // The user's attempts keyed by "assessment_id-batch_id".
    private function attemptsMap(User $user)
    {
        return AssessmentAttempt::where('user_id', $user->id)
            ->get()
            ->keyBy(fn ($attempt) => $attempt->assessment_id.'-'.$attempt->batch_id);
    }

    private function attemptFor($attempts, $assignment)
    {
        return $attempts->get($assignment->assessment_id.'-'.$assignment->batch_id);
    }

    // Pass $attempts to also include each row's attempt_state().
    private function mapWithBatch($assignments, $batches, $attempts = null)
    {
        return $assignments->map(function ($assignment) use ($batches, $attempts) {
            $assessment = $assignment->assessment;
            $batch = $batches[$assignment->batch_id] ?? null;

            $row = [...$assessment->toArray(), ...batch_schedule_fields($assessment, $batch)];

            if ($attempts !== null) {
                $row = [...$row, ...attempt_state($assessment, $batch, $this->attemptFor($attempts, $assignment))];
            }

            return $row;
        })->values();
    }

    private function assignments(User $user)
    {
        // An assessment soft-deleted by its admin leaves its assignments behind
        // with a null relation — skip them rather than crash every listing.
        //
        // Only the newest assignment per assessment counts: after a re-exam,
        // start/submit/result all act on the re-exam, so listing the original
        // too would show a card whose actions hit the wrong attempt.
        return AssessmentAssignment::where('user_id', $user->id)
            ->with('assessment')
            ->orderByDesc('id')
            ->get()
            ->filter(fn ($assignment) => $assignment->assessment !== null)
            ->unique('assessment_id')
            ->values();
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
        $attempts = $this->attemptsMap($user);

        $filtered = $assignments->filter(function ($assignment) use ($today, $nowTime, $batches, $attempts) {
            $assessment = $assignment->assessment;
            $batch = $batches[$assignment->batch_id] ?? null;
            $attempt = $this->attemptFor($attempts, $assignment);

            if (!$batch) {
                return false;
            }

            if ($attempt) {
                // Started but not submitted stays "running" until the student
                // submits — even after the window closes (answering is blocked
                // then, see attempt_state()) or the assessment is deactivated.
                return !$attempt->submitted_at;
            }

            if (!$assessment->is_active) {
                return false;
            }

            return $assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE
                ? (!$batch->publish_date || $batch->publish_date <= $today) && (!$batch->expiry_date || $batch->expiry_date >= $today)
                : $batch->publish_date == $today && $batch->start_time <= $nowTime && $batch->end_time >= $nowTime;
        });

        return $this->mapWithBatch($filtered, $batches, $attempts)->all();
    }

    public function completed(User $user): array
    {
        $assignments = $this->assignments($user);
        $batches = $this->batchesMap($assignments);
        $attempts = $this->attemptsMap($user);

        // Submitted only — an unsubmitted attempt is still listed under running().
        $filtered = $assignments->filter(fn ($assignment) => $this->attemptFor($attempts, $assignment)?->submitted_at !== null);

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
            ->whereHas('assessment')
            ->with('assessment')
            ->latest('id')
            ->firstOrFail();

        $batches = $this->batchesMap(collect([$assignment]));

        return $this->mapWithBatch(collect([$assignment]), $batches, $this->attemptsMap($user))->first();
    }
}
