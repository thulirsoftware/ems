<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\Batch;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    // Helper to key an attempt by the assessment + batch it belongs to
    private function attemptKey($assessmentId, $batchId)
    {
        return $assessmentId . '-' . $batchId;
    }

    // Helper to preload the batches referenced by a set of assignments
    private function getBatchesMap($assignments)
    {
        $batchIds = $assignments->pluck('batch_id')->filter()->unique();

        return Batch::whereIn('id', $batchIds)->get()->keyBy('id');
    }

    // Helper to load the user's assignments deduped per assessment + batch
    private function loadAssignments($userId)
    {
        return AssessmentAssignment::where('user_id', $userId)
            ->with('assessment')
            ->orderByDesc('id')
            ->get()
            ->unique(fn($assignment) => $this->attemptKey(
                $assignment->assessment_id,
                $assignment->batch_id
            ))
            ->values();
    }

    // Helper to load the user's attempts keyed by assessment + batch
    private function loadAttempts($userId)
    {
        return AssessmentAttempt::where('user_id', $userId)
            ->with('assessment:id,title,scheduling_type')
            ->get()
            ->keyBy(fn($attempt) => $this->attemptKey(
                $attempt->assessment_id,
                $attempt->batch_id
            ));
    }

    // Helper to bucket one assignment into a dashboard status
    private function classify($assessment, $batch, $attempt, $today, $nowTime)
    {
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

            return !$batch->expiry_date || $batch->expiry_date >= $today
                ? 'available'
                : 'missed';
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
    }

    // Every assignment paired with its batch, attempt and computed status
    private function buildRows($assignments, $batches, $attempts, $today, $nowTime)
    {
        return $assignments->map(function ($assignment) use ($batches, $attempts, $today, $nowTime) {

            $batch = $batches[$assignment->batch_id] ?? null;

            $attempt = $attempts->get($this->attemptKey(
                $assignment->assessment_id,
                $assignment->batch_id
            ));

            return [
                'assessment' => $assignment->assessment,
                'batch' => $batch,
                'attempt' => $attempt,
                'status' => $this->classify($assignment->assessment, $batch, $attempt, $today, $nowTime),
            ];
        });
    }

    // Counters per assignment status
    private function assignmentStats($rows)
    {
        $counts = [
            'assigned' => $rows->count(),
            'available' => 0,
            'upcoming' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'missed' => 0,
        ];

        foreach ($rows as $row) {

            if (isset($counts[$row['status']])) {
                $counts[$row['status']]++;
            }
        }

        return $counts;
    }

    // Result counters across every submitted attempt
    private function resultStats($submitted, $percentages)
    {
        return [
            'submitted' => $submitted->count(),
            'pending_evaluation' => $submitted->where('score', 'Pending Evaluation')->count(),
            'evaluated' => $percentages->count(),

            'average_percentage' => $percentages->count() > 0
                ? round($percentages->avg())
                : 0,

            'best_percentage' => $percentages->count() > 0
                ? $percentages->max()
                : 0,

            'lowest_percentage' => $percentages->count() > 0
                ? $percentages->min()
                : 0,
        ];
    }

    // Assessment rows the user can act on right now
    private function actionable($rows, $status, $limit)
    {
        return $rows
            ->where('status', $status)
            ->take($limit)
            ->map(fn($row) => [
                'assessment_id' => $row['assessment']?->id,
                'assessment_title' => $row['assessment']?->title,
                'scheduling_type' => $row['assessment']?->scheduling_type,
                ...batch_schedule_fields($row['assessment'], $row['batch']),
            ])
            ->values();
    }

    // Latest submitted attempts with their outcome
    private function recentResults($submitted, $batches, $limit)
    {
        return $submitted
            ->sortByDesc('id')
            ->take($limit)
            ->map(function ($attempt) use ($batches) {

                $result = attempt_result($attempt);

                return [
                    'attempt_id' => $attempt->id,
                    'assessment_id' => $attempt->assessment_id,
                    'assessment_title' => $attempt->assessment?->title,
                    'batch_id' => is_implicit_batch($attempt->assessment, $batches->get($attempt->batch_id))
                        ? null
                        : $attempt->batch_id,
                    'submitted_at' => $attempt->submitted_at,
                    'status' => $result ? 'evaluated' : 'pending_evaluation',
                    'score' => $result['score'] ?? null,
                    'total_marks' => $result['total'] ?? null,
                    'percentage' => $result['percentage'] ?? null,
                ];
            })
            ->values();
    }

    // Evaluated attempts oldest first, for a progress chart
    private function performance($submitted, $batches)
    {
        return $submitted
            ->sortBy('id')
            ->map(function ($attempt) use ($batches) {

                $result = attempt_result($attempt);

                if (!$result) {
                    return null;
                }

                return [
                    'attempt_id' => $attempt->id,
                    'assessment_id' => $attempt->assessment_id,
                    'assessment_title' => $attempt->assessment?->title,
                    'batch_id' => is_implicit_batch($attempt->assessment, $batches->get($attempt->batch_id))
                        ? null
                        : $attempt->batch_id,
                    'submitted_at' => $attempt->submitted_at,
                    'score' => $result['score'],
                    'total_marks' => $result['total'],
                    'percentage' => $result['percentage'],
                ];
            })
            ->filter()
            ->values();
    }

    public function index(Request $request)
    {
        $user = $request->user('users');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();
        $limit = resolve_limit($request);

        $assignments = $this->loadAssignments($user->id);
        $attempts = $this->loadAttempts($user->id);
        $batches = $this->getBatchesMap($assignments->concat($attempts));

        $rows = $this->buildRows($assignments, $batches, $attempts, $today, $nowTime);

        $submitted = $attempts->filter(fn($attempt) => $attempt->submitted_at !== null);

        $percentages = $submitted
            ->map(fn($attempt) => attempt_result($attempt))
            ->filter()
            ->pluck('percentage');

        return response()->json([
            'assignments' => $this->assignmentStats($rows),
            'results' => $this->resultStats($submitted, $percentages),

            'available_now' => $this->actionable($rows, 'available', $limit),
            'upcoming' => $this->actionable($rows, 'upcoming', $limit),
            'in_progress' => $this->actionable($rows, 'in_progress', $limit),

            'recent_results' => $this->recentResults($submitted, $batches, $limit),
            'performance' => $this->performance($submitted, $batches),
        ]);
    }
}
