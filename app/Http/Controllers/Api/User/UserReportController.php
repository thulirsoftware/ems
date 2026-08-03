<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserReportController extends Controller
{
    // Shared filter rules for every report
    private function validateFilters($request)
    {
        return $request->validate([
            'assessment_id' => 'nullable|exists:assessments,id',
            'batch_id' => 'nullable|exists:batches,id',

            'status' => 'nullable|in:in_progress,pending_evaluation,evaluated',
            'passing_percentage' => 'nullable|numeric|min:0|max:100',

            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',

            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:200',
        ]);
    }

    // The user's own attempts, narrowed by the filters.
    // NOTE: started_at/submitted_at are TIME columns with no date part, so the
    // from/to range is applied to created_at.
    private function scopedAttempts($userId, $validated)
    {
        $query = AssessmentAttempt::where('user_id', $userId)
            ->with(['assessment:id,title,assessment_type_id', 'assessment.type:id,slug']);

        if (!empty($validated['assessment_id'])) {
            $query->where('assessment_id', $validated['assessment_id']);
        }

        if (!empty($validated['batch_id'])) {
            $query->where('batch_id', $validated['batch_id']);
        }

        if (!empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }

        if (!empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $attempts = $query->get();

        if (!empty($validated['status'])) {

            $attempts = $attempts->filter(function ($attempt) use ($validated) {

                $result = attempt_result($attempt);

                return attempt_status($attempt, $result) === $validated['status'];
            })->values();
        }

        return $attempts;
    }

    // The user's own assignments, narrowed the same way
    private function scopedAssignments($userId, $validated)
    {
        $query = AssessmentAssignment::where('user_id', $userId);

        if (!empty($validated['assessment_id'])) {
            $query->where('assessment_id', $validated['assessment_id']);
        }

        if (!empty($validated['batch_id'])) {
            $query->where('batch_id', $validated['batch_id']);
        }

        return $query->get();
    }

    // 1. Headline report for the logged-in user
    public function summary(Request $request)
    {
        $user = $request->user('users');

        $validated = $this->validateFilters($request);
        $passing = passing_percentage($validated);

        $attempts = $this->scopedAttempts($user->id, $validated);
        $assignments = $this->scopedAssignments($user->id, $validated);

        $submitted = $attempts->filter(fn($attempt) => $attempt->submitted_at !== null);

        return response()->json([
            'filters' => applied_filters($validated),
            'passing_percentage' => $passing,

            'totals' => [
                'assigned' => $assignments->count(),
                'assessments' => $assignments->pluck('assessment_id')->unique()->count(),
            ],

            'attempts' => [
                'total' => $attempts->count(),
                'in_progress' => $attempts->count() - $submitted->count(),
                'submitted' => $submitted->count(),
                'pending_evaluation' => $submitted->where('score', 'Pending Evaluation')->count(),
            ],

            'participation' => [
                'assigned' => $assignments->count(),
                'attempted' => $attempts->count(),
                'not_attempted' => max(0, $assignments->count() - $attempts->count()),
                'participation_rate' => $assignments->count() > 0
                    ? round(($attempts->count() / $assignments->count()) * 100)
                    : 0,
            ],

            'performance' => score_performance($attempts, $passing),
            'score_distribution' => score_distribution($attempts),
        ]);
    }

    // 2. One row per assessment the user has taken
    public function assessments(Request $request)
    {
        $user = $request->user('users');

        $validated = $this->validateFilters($request);
        $passing = passing_percentage($validated);

        $attempts = $this->scopedAttempts($user->id, $validated);
        $assignments = $this->scopedAssignments($user->id, $validated)->groupBy('assessment_id');

        $rows = $attempts->groupBy('assessment_id')->map(function ($own, $assessmentId) use ($assignments, $passing) {

            $first = $own->first();
            $submitted = $own->filter(fn($attempt) => $attempt->submitted_at !== null);

            return [
                'assessment_id' => (int) $assessmentId,
                'assessment_title' => $first->assessment?->title,
                'type' => $first->assessment?->type?->slug,

                'assigned' => $assignments->get($assessmentId, collect())->count(),
                'attempts' => $own->count(),
                'in_progress' => $own->count() - $submitted->count(),
                'submitted' => $submitted->count(),
                'pending_evaluation' => $submitted->where('score', 'Pending Evaluation')->count(),

                ...score_performance($own, $passing),
            ];
        })->sortByDesc('average_percentage')->values();

        return response()->json([
            'filters' => applied_filters($validated),
            'passing_percentage' => $passing,
            ...paginate_rows($rows, $validated),
        ]);
    }

    // 3. Detailed attempt history
    public function attempts(Request $request)
    {
        $user = $request->user('users');

        $validated = $this->validateFilters($request);
        $passing = passing_percentage($validated);

        $attempts = $this->scopedAttempts($user->id, $validated);

        $batches = Batch::whereIn('id', $attempts->pluck('batch_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $rows = $attempts->sortByDesc('id')->map(function ($attempt) use ($batches, $passing) {

            $result = attempt_result($attempt);
            $percentage = $result['percentage'] ?? null;

            return [
                'attempt_id' => $attempt->id,
                'assessment_id' => $attempt->assessment_id,
                'assessment_title' => $attempt->assessment?->title,
                'type' => $attempt->assessment?->type?->slug,
                'batch_id' => $attempt->batch_id,
                'batch_name' => $batches->get($attempt->batch_id)?->name,

                'attempt_date' => $attempt->created_at?->toDateString(),
                'started_at' => $attempt->started_at,
                'submitted_at' => $attempt->submitted_at,

                'status' => attempt_status($attempt, $result),
                'score' => $result['score'] ?? null,
                'total_marks' => $result['total'] ?? null,
                'percentage' => $percentage,
                'passed' => $percentage === null ? null : $percentage >= $passing,
            ];
        })->values();

        return response()->json([
            'filters' => applied_filters($validated),
            'passing_percentage' => $passing,
            ...paginate_rows($rows, $validated),
        ]);
    }

    // 4. Question level breakdown of the user's own answers.
    // Only submitted attempts are included, so this never reveals correctness
    // for an attempt that is still in progress.
    public function questions(Request $request)
    {
        $user = $request->user('users');

        $validated = $this->validateFilters($request);

        $attempts = $this->scopedAttempts($user->id, $validated)
            ->filter(fn($attempt) => $attempt->submitted_at !== null);

        $attemptsById = $attempts->keyBy('id');

        $answers = DB::table('assessment_answers')
            ->whereIn('attempt_id', $attempts->pluck('id'))
            ->whereNull('deleted_at')
            ->get();

        $questions = AssessmentQuestion::whereIn('id', $answers->pluck('question_id')->unique())
            ->get()
            ->keyBy('id');

        $rows = $answers->map(function ($answer) use ($questions, $attemptsById) {

            $question = $questions->get($answer->question_id);
            $attempt = $attemptsById->get($answer->attempt_id);

            return [
                'attempt_id' => $answer->attempt_id,
                'assessment_id' => $attempt?->assessment_id,
                'assessment_title' => $attempt?->assessment?->title,
                'batch_id' => $attempt?->batch_id,

                'question_id' => $answer->question_id,
                'question_text' => $question?->question_text,
                'type' => $question?->type,
                'order' => $question?->order,

                'answer' => json_decode($answer->answer, true),
                'is_correct' => $answer->is_correct === null
                    ? null
                    : (bool) $answer->is_correct,
                'graded' => $answer->is_correct !== null,
            ];
        })
            ->sortBy([['assessment_id', 'asc'], ['order', 'asc']])
            ->values();

        $graded = $rows->where('graded', true);

        return response()->json([
            'filters' => applied_filters($validated),

            'totals' => [
                'answers' => $rows->count(),
                'graded' => $graded->count(),
                'correct' => $graded->where('is_correct', true)->count(),
                'wrong' => $graded->where('is_correct', false)->count(),
                'accuracy' => $graded->count() > 0
                    ? round(($graded->where('is_correct', true)->count() / $graded->count()) * 100)
                    : 0,
            ],

            ...paginate_rows($rows, $validated),
        ]);
    }

    // 5. Evaluated attempts over time with a running average
    public function progress(Request $request)
    {
        $user = $request->user('users');

        $validated = $this->validateFilters($request);
        $passing = passing_percentage($validated);

        $attempts = $this->scopedAttempts($user->id, $validated)->sortBy('id');

        $running = [];
        $rows = [];

        foreach ($attempts as $attempt) {

            $result = attempt_result($attempt);

            if (!$result) {
                continue;
            }

            $running[] = $result['percentage'];

            $rows[] = [
                'attempt_id' => $attempt->id,
                'assessment_id' => $attempt->assessment_id,
                'assessment_title' => $attempt->assessment?->title,
                'batch_id' => $attempt->batch_id,

                'attempt_date' => $attempt->created_at?->toDateString(),
                'submitted_at' => $attempt->submitted_at,

                'score' => $result['score'],
                'total_marks' => $result['total'],
                'percentage' => $result['percentage'],
                'passed' => $result['percentage'] >= $passing,
                'running_average' => round(array_sum($running) / count($running)),
            ];
        }

        $rows = collect($rows);

        return response()->json([
            'filters' => applied_filters($validated),
            'passing_percentage' => $passing,

            'trend' => $rows->count() > 1
                ? round($rows->last()['percentage'] - $rows->first()['percentage'])
                : 0,

            ...paginate_rows($rows, $validated),
        ]);
    }
}
