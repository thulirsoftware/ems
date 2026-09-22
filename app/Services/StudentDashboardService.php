<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\Batch;
use App\Models\User;

class StudentDashboardService
{
    public function get(User $user, int $limit = 5): array
    {
        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $key = fn ($assessmentId, $batchId) => $assessmentId.'-'.$batchId;

        $assignments = AssessmentAssignment::where('user_id', $user->id)
            ->with('assessment')
            ->orderByDesc('id')
            ->get()
            ->unique(fn ($assignment) => $key($assignment->assessment_id, $assignment->batch_id))
            ->values();

        $attempts = AssessmentAttempt::where('user_id', $user->id)
            ->with('assessment:id,title,scheduling_type')
            ->get()
            ->keyBy(fn ($attempt) => $key($attempt->assessment_id, $attempt->batch_id));

        $batches = Batch::whereIn('id', $assignments->concat($attempts)->pluck('batch_id')->filter()->unique())->get()->keyBy('id');

        $classify = function ($assessment, $batch, $attempt) use ($today, $nowTime) {
            if ($attempt) {
                return $attempt->submitted_at ? 'completed' : 'in_progress';
            }

            if (!$assessment || !$assessment->is_active) {
                return 'inactive';
            }

            if (!$batch) {
                return 'unscheduled';
            }

            if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
                if ($batch->publish_date && $batch->publish_date > $today) {
                    return 'upcoming';
                }

                return !$batch->expiry_date || $batch->expiry_date >= $today ? 'available' : 'missed';
            }

            if (!$batch->publish_date) {
                return 'unscheduled';
            }

            if ($batch->publish_date > $today) {
                return 'upcoming';
            }

            if ($batch->publish_date < $today) {
                return 'missed';
            }

            if ($batch->start_time > $nowTime) {
                return 'upcoming';
            }

            if ($batch->end_time < $nowTime) {
                return 'missed';
            }

            return 'available';
        };

        $rows = $assignments->map(function ($assignment) use ($batches, $attempts, $classify, $key) {
            $batch = $batches[$assignment->batch_id] ?? null;
            $attempt = $attempts->get($key($assignment->assessment_id, $assignment->batch_id));

            return [
                'assessment' => $assignment->assessment,
                'batch' => $batch,
                'attempt' => $attempt,
                'status' => $classify($assignment->assessment, $batch, $attempt),
            ];
        });

        $assignmentStats = ['assigned' => $rows->count(), 'available' => 0, 'upcoming' => 0, 'in_progress' => 0, 'completed' => 0, 'missed' => 0];

        foreach ($rows as $row) {
            if (isset($assignmentStats[$row['status']])) {
                $assignmentStats[$row['status']]++;
            }
        }

        $submitted = $attempts->filter(fn ($attempt) => $attempt->submitted_at !== null);
        $percentages = $submitted->map(fn ($attempt) => attempt_result($attempt))->filter()->pluck('percentage');

        $resultStats = [
            'submitted' => $submitted->count(),
            'pending_evaluation' => $submitted->where('score', 'Pending Evaluation')->count(),
            'evaluated' => $percentages->count(),
            'average_percentage' => $percentages->count() > 0 ? round($percentages->avg()) : 0,
            'best_percentage' => $percentages->count() > 0 ? $percentages->max() : 0,
            'lowest_percentage' => $percentages->count() > 0 ? $percentages->min() : 0,
        ];

        $actionable = fn ($status) => $rows->where('status', $status)->take($limit)->map(fn ($row) => [
            'assessment_id' => $row['assessment']?->id,
            'assessment_title' => $row['assessment']?->title,
            'scheduling_type' => $row['assessment']?->scheduling_type,
            ...batch_schedule_fields($row['assessment'], $row['batch']),
        ])->values();

        $recentResults = $submitted->sortByDesc('id')->take($limit)->map(function ($attempt) use ($batches) {
            $result = attempt_result($attempt);

            return [
                'attempt_id' => $attempt->id,
                'assessment_id' => $attempt->assessment_id,
                'assessment_title' => $attempt->assessment?->title,
                'batch_id' => is_implicit_batch($attempt->assessment, $batches->get($attempt->batch_id)) ? null : $attempt->batch_id,
                'submitted_at' => $attempt->submitted_at,
                'status' => $result ? 'evaluated' : 'pending_evaluation',
                'score' => $result['score'] ?? null,
                'total_marks' => $result['total'] ?? null,
                'percentage' => $result['percentage'] ?? null,
            ];
        })->values();

        $performance = $submitted->sortBy('id')->map(function ($attempt) use ($batches) {
            $result = attempt_result($attempt);

            if (!$result) {
                return null;
            }

            return [
                'attempt_id' => $attempt->id,
                'assessment_id' => $attempt->assessment_id,
                'assessment_title' => $attempt->assessment?->title,
                'batch_id' => is_implicit_batch($attempt->assessment, $batches->get($attempt->batch_id)) ? null : $attempt->batch_id,
                'submitted_at' => $attempt->submitted_at,
                'score' => $result['score'],
                'total_marks' => $result['total'],
                'percentage' => $result['percentage'],
            ];
        })->filter()->values();

        return [
            'assignments' => $assignmentStats,
            'results' => $resultStats,
            'available_now' => $actionable('available'),
            'upcoming' => $actionable('upcoming'),
            'in_progress' => $actionable('in_progress'),
            'recent_results' => $recentResults,
            'performance' => $performance,
        ];
    }
}
