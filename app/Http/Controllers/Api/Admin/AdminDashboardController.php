<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\Batch;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    // Assessment counters, including a breakdown per assessment type
    private function assessmentStats($assessments)
    {
        $byType = $assessments
            ->groupBy(fn($assessment) => $assessment->type?->slug ?? 'unknown')
            ->map(fn($group) => $group->count());

        return [
            'total' => $assessments->count(),
            'active' => $assessments->where('is_active', true)->count(),
            'library' => $assessments->where('is_library', true)->count(),
            'batch_wise' => $assessments->where('is_batch_wise', true)->count(),
            'flexible' => $assessments->where('is_flexible', true)->count(),
            'by_type' => $byType,
        ];
    }

    // Batch counters split by schedule status
    private function batchStats($assessments, $batches, $today, $nowTime)
    {
        $counts = [
            'total' => $batches->count(),
            'upcoming' => 0,
            'running' => 0,
            'finished' => 0,
        ];

        foreach ($batches as $batch) {

            $assessment = $assessments->get($batch->assessment_id);

            if (!$assessment) {
                continue;
            }

            $counts[batch_status($assessment, $batch, $today, $nowTime)]++;
        }

        return $counts;
    }

    // Attempt counters plus the completion rate against assignments
    private function attemptStats($attempts, $submitted, $evaluatedCount, $assignmentCount)
    {
        return [
            'total' => $attempts->count(),
            'in_progress' => $attempts->count() - $submitted->count(),
            'submitted' => $submitted->count(),
            'pending_evaluation' => $submitted->where('score', 'Pending Evaluation')->count(),
            'evaluated' => $evaluatedCount,

            'completion_rate' => $assignmentCount > 0
                ? round(($submitted->count() / $assignmentCount) * 100)
                : 0,
        ];
    }

    // Highest average scorers across the admin's evaluated attempts
    private function topPerformers($submitted, $limit)
    {
        return $submitted
            ->map(function ($attempt) {

                $result = attempt_result($attempt);

                if (!$result) {
                    return null;
                }

                return [
                    'user_id' => $attempt->user_id,
                    'user_name' => $attempt->user?->name,
                    'percentage' => $result['percentage'],
                ];
            })
            ->filter()
            ->groupBy('user_id')
            ->map(fn($rows) => [
                'user_id' => $rows->first()['user_id'],
                'user_name' => $rows->first()['user_name'],
                'attempts' => $rows->count(),
                'average_percentage' => round($rows->avg('percentage')),
            ])
            ->sortByDesc('average_percentage')
            ->take($limit)
            ->values();
    }

    // Next scheduled batches, soonest first
    private function upcomingBatches($assessments, $batches, $today, $nowTime, $limit)
    {
        return $batches
            ->map(function ($batch) use ($assessments, $today, $nowTime) {

                $assessment = $assessments->get($batch->assessment_id);

                if (!$assessment) {
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
            // date then time, unscheduled batches last
            ->sortBy(fn($row) => ($row['publish_date'] ?? '9999-12-31') . ' ' . ($row['start_time'] ?? ''))
            ->take($limit)
            ->values();
    }

    // Latest attempts across every owned assessment
    private function recentAttempts($attempts, $limit)
    {
        return $attempts
            ->sortByDesc('id')
            ->take($limit)
            ->map(function ($attempt) {

                $result = attempt_result($attempt);

                return [
                    'attempt_id' => $attempt->id,
                    'assessment_id' => $attempt->assessment_id,
                    'assessment_title' => $attempt->assessment?->title,
                    'batch_id' => $attempt->batch_id,
                    'user_id' => $attempt->user_id,
                    'user_name' => $attempt->user?->name,
                    'started_at' => $attempt->started_at,
                    'submitted_at' => $attempt->submitted_at,
                    'status' => !$attempt->submitted_at
                        ? 'in_progress'
                        : ($result ? 'evaluated' : 'pending_evaluation'),
                    'score' => $result['score'] ?? null,
                    'total_marks' => $result['total'] ?? null,
                    'percentage' => $result['percentage'] ?? null,
                ];
            })
            ->values();
    }

    // Submitted attempts still waiting on manual grading
    private function pendingEvaluations($submitted, $limit)
    {
        return $submitted
            ->where('score', 'Pending Evaluation')
            ->sortByDesc('id')
            ->take($limit)
            ->map(fn($attempt) => [
                'attempt_id' => $attempt->id,
                'assessment_id' => $attempt->assessment_id,
                'assessment_title' => $attempt->assessment?->title,
                'batch_id' => $attempt->batch_id,
                'user_id' => $attempt->user_id,
                'user_name' => $attempt->user?->name,
                'submitted_at' => $attempt->submitted_at,
            ])
            ->values();
    }

    public function index(Request $request)
    {
        $admin = $request->user('admins');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();
        $limit = resolve_limit($request);

        $assessments = Assessment::where('admin_id', $admin->id)
            ->with('type:id,name,slug')
            ->get();

        $assessmentIds = $assessments->pluck('id');
        $assessmentsById = $assessments->keyBy('id');

        $batches = Batch::whereIn('assessment_id', $assessmentIds)->get();

        $attempts = AssessmentAttempt::whereIn('assessment_id', $assessmentIds)
            ->with(['user:id,name', 'assessment:id,title'])
            ->get();

        $submitted = $attempts->filter(fn($attempt) => $attempt->submitted_at !== null);

        $percentages = $submitted
            ->map(fn($attempt) => attempt_result($attempt))
            ->filter()
            ->pluck('percentage');

        $assignmentCount = AssessmentAssignment::whereIn('assessment_id', $assessmentIds)->count();

        return response()->json([
            'assessments' => $this->assessmentStats($assessments),
            'batches' => $this->batchStats($assessmentsById, $batches, $today, $nowTime),

            'questions' => AssessmentQuestion::whereIn('assessment_id', $assessmentIds)->count(),

            'candidates' => AssessmentAssignment::whereIn('assessment_id', $assessmentIds)
                ->distinct('user_id')
                ->count('user_id'),

            'assignments' => $assignmentCount,

            'attempts' => $this->attemptStats(
                $attempts,
                $submitted,
                $percentages->count(),
                $assignmentCount
            ),

            'average_percentage' => $percentages->count() > 0
                ? round($percentages->avg())
                : 0,

            'score_distribution' => bucket_percentages($percentages),
            'top_performers' => $this->topPerformers($submitted, $limit),
            'upcoming_batches' => $this->upcomingBatches($assessmentsById, $batches, $today, $nowTime, $limit),
            'recent_attempts' => $this->recentAttempts($attempts, $limit),
            'pending_evaluations' => $this->pendingEvaluations($submitted, $limit),
        ]);
    }
}
