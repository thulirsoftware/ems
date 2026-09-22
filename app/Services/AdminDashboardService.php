<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\Batch;

class AdminDashboardService
{
    public function get(Admin $admin, int $limit = 5): array
    {
        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = Assessment::where('admin_id', $admin->id)->with('type:id,name,slug')->get();
        $assessmentIds = $assessments->pluck('id');
        $assessmentsById = $assessments->keyBy('id');

        $batches = Batch::whereIn('assessment_id', $assessmentIds)->get();
        $batchesById = $batches->keyBy('id');

        $attempts = AssessmentAttempt::whereIn('assessment_id', $assessmentIds)
            ->with(['user:id,name', 'assessment:id,title,scheduling_type'])
            ->get();

        $submitted = $attempts->filter(fn ($attempt) => $attempt->submitted_at !== null);
        $percentages = $submitted->map(fn ($attempt) => attempt_result($attempt))->filter()->pluck('percentage');
        $assignmentCount = AssessmentAssignment::whereIn('assessment_id', $assessmentIds)->count();

        $byType = $assessments->groupBy(fn ($a) => $a->type?->slug ?? 'unknown')->map(fn ($group) => $group->count());

        $batchCounts = ['total' => $batches->count(), 'upcoming' => 0, 'running' => 0, 'finished' => 0];

        foreach ($batches as $batch) {
            $assessment = $assessmentsById->get($batch->assessment_id);

            if ($assessment) {
                $batchCounts[batch_status($assessment, $batch, $today, $nowTime)]++;
            }
        }

        $topPerformers = $submitted
            ->map(function ($attempt) {
                $result = attempt_result($attempt);

                return $result ? ['user_id' => $attempt->user_id, 'user_name' => $attempt->user?->name, 'percentage' => $result['percentage']] : null;
            })
            ->filter()
            ->groupBy('user_id')
            ->map(fn ($rows) => [
                'user_id' => $rows->first()['user_id'],
                'user_name' => $rows->first()['user_name'],
                'attempts' => $rows->count(),
                'average_percentage' => round($rows->avg('percentage')),
            ])
            ->sortByDesc('average_percentage')
            ->take($limit)
            ->values();

        $upcomingBatches = $batches
            ->map(function ($batch) use ($assessmentsById, $today, $nowTime) {
                $assessment = $assessmentsById->get($batch->assessment_id);

                if (!$assessment || $assessment->scheduling_type !== Assessment::SCHEDULING_BATCH_WISE) {
                    return null;
                }

                if (batch_status($assessment, $batch, $today, $nowTime) !== 'upcoming') {
                    return null;
                }

                return [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'assessment_id' => $assessment->id,
                    'assessment_title' => $assessment->title,
                    'publish_date' => $batch->publish_date,
                    'start_time' => $batch->start_time,
                    'end_time' => $batch->end_time,
                    'capacity' => $batch->capacity,
                ];
            })
            ->filter()
            ->sortBy(fn ($row) => ($row['publish_date'] ?? '9999-12-31').' '.($row['start_time'] ?? ''))
            ->take($limit)
            ->values();

        $recentAttempts = $attempts
            ->sortByDesc('id')
            ->take($limit)
            ->map(function ($attempt) use ($batchesById) {
                $result = attempt_result($attempt);

                return [
                    'attempt_id' => $attempt->id,
                    'assessment_id' => $attempt->assessment_id,
                    'assessment_title' => $attempt->assessment?->title,
                    'batch_id' => is_implicit_batch($attempt->assessment, $batchesById->get($attempt->batch_id)) ? null : $attempt->batch_id,
                    'user_id' => $attempt->user_id,
                    'user_name' => $attempt->user?->name,
                    'started_at' => $attempt->started_at,
                    'submitted_at' => $attempt->submitted_at,
                    'status' => !$attempt->submitted_at ? 'in_progress' : ($result ? 'evaluated' : 'pending_evaluation'),
                    'score' => $result['score'] ?? null,
                    'total_marks' => $result['total'] ?? null,
                    'percentage' => $result['percentage'] ?? null,
                ];
            })
            ->values();

        $pendingEvaluations = $submitted
            ->where('score', 'Pending Evaluation')
            ->sortByDesc('id')
            ->take($limit)
            ->map(fn ($attempt) => [
                'attempt_id' => $attempt->id,
                'assessment_id' => $attempt->assessment_id,
                'assessment_title' => $attempt->assessment?->title,
                'batch_id' => is_implicit_batch($attempt->assessment, $batchesById->get($attempt->batch_id)) ? null : $attempt->batch_id,
                'user_id' => $attempt->user_id,
                'user_name' => $attempt->user?->name,
                'submitted_at' => $attempt->submitted_at,
            ])
            ->values();

        return [
            'assessments' => [
                'total' => $assessments->count(),
                'active' => $assessments->where('is_active', true)->count(),
                'library' => $assessments->where('is_library', true)->count(),
                'batch_wise' => $assessments->where('scheduling_type', Assessment::SCHEDULING_BATCH_WISE)->count(),
                'flexible' => $assessments->where('scheduling_type', Assessment::SCHEDULING_FLEXIBLE)->count(),
                'by_type' => $byType,
            ],
            'batches' => $batchCounts,
            'questions' => AssessmentQuestion::whereIn('assessment_id', $assessmentIds)->count(),
            'candidates' => AssessmentAssignment::whereIn('assessment_id', $assessmentIds)->distinct('user_id')->count('user_id'),
            'assignments' => $assignmentCount,
            'attempts' => [
                'total' => $attempts->count(),
                'in_progress' => $attempts->count() - $submitted->count(),
                'submitted' => $submitted->count(),
                'pending_evaluation' => $submitted->where('score', 'Pending Evaluation')->count(),
                'evaluated' => $percentages->count(),
                'completion_rate' => $assignmentCount > 0 ? round(($submitted->count() / $assignmentCount) * 100) : 0,
            ],
            'average_percentage' => $percentages->count() > 0 ? round($percentages->avg()) : 0,
            'score_distribution' => bucket_percentages($percentages),
            'top_performers' => $topPerformers,
            'upcoming_batches' => $upcomingBatches,
            'recent_attempts' => $recentAttempts,
            'pending_evaluations' => $pendingEvaluations,
        ];
    }
}
