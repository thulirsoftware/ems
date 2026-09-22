<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminReportService
{
    public function validateFilters(array $data): array
    {
        return Validator::make($data, [
            'assessment_id' => 'nullable|exists:assessments,id',
            'batch_id' => 'nullable|exists:batches,id',
            'user_id' => 'nullable|exists:users,id',
            'assessment_type' => 'nullable|string',
            'status' => 'nullable|in:in_progress,pending_evaluation,evaluated',
            'passing_percentage' => 'nullable|numeric|min:0|max:100',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:200',
        ])->validate();
    }

    private function scopedAssessments(Admin $admin, array $validated)
    {
        $query = Assessment::where('admin_id', $admin->id)->with('type:id,name,slug');

        if (!empty($validated['assessment_id'])) {
            $query->where('id', $validated['assessment_id']);
        }

        if (!empty($validated['assessment_type'])) {
            $query->whereHas('type', fn ($q) => $q->where('slug', $validated['assessment_type']));
        }

        return $query->get();
    }

    private function scopedAttempts($assessmentIds, array $validated, bool $withRelations = true)
    {
        $query = AssessmentAttempt::whereIn('assessment_id', $assessmentIds);

        if ($withRelations) {
            $query->with(['user:id,name,email', 'assessment:id,title,scheduling_type']);
        }

        if (!empty($validated['batch_id'])) {
            $query->where('batch_id', $validated['batch_id']);
        }

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (!empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }

        if (!empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $attempts = $query->get();

        if (!empty($validated['status'])) {
            $attempts = $attempts->filter(fn ($attempt) => attempt_status($attempt, attempt_result($attempt)) === $validated['status'])->values();
        }

        return $attempts;
    }

    private function scopedAssignments($assessmentIds, array $validated)
    {
        $query = AssessmentAssignment::whereIn('assessment_id', $assessmentIds);

        if (!empty($validated['batch_id'])) {
            $query->where('batch_id', $validated['batch_id']);
        }

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        return $query->get();
    }

    public function summary(Admin $admin, array $data): array
    {
        $validated = $this->validateFilters($data);
        $passing = passing_percentage($validated);

        $assessments = $this->scopedAssessments($admin, $validated);
        $assessmentIds = $assessments->pluck('id');

        $attempts = $this->scopedAttempts($assessmentIds, $validated);
        $assignments = $this->scopedAssignments($assessmentIds, $validated);

        $submitted = $attempts->filter(fn ($attempt) => $attempt->submitted_at !== null);
        $attemptedKeys = $attempts->map(fn ($a) => $a->user_id.'-'.$a->assessment_id.'-'.$a->batch_id)->unique();

        return [
            'filters' => applied_filters($validated),
            'passing_percentage' => $passing,
            'totals' => [
                'assessments' => $assessments->count(),
                'batches' => Batch::whereIn('assessment_id', $assessmentIds)->count(),
                'questions' => AssessmentQuestion::whereIn('assessment_id', $assessmentIds)->count(),
                'candidates' => $assignments->pluck('user_id')->unique()->count(),
                'assignments' => $assignments->count(),
            ],
            'attempts' => [
                'total' => $attempts->count(),
                'in_progress' => $attempts->count() - $submitted->count(),
                'submitted' => $submitted->count(),
                'pending_evaluation' => $submitted->where('score', 'Pending Evaluation')->count(),
            ],
            'participation' => [
                'assigned' => $assignments->count(),
                'attempted' => $attemptedKeys->count(),
                'not_attempted' => max(0, $assignments->count() - $attemptedKeys->count()),
                'participation_rate' => $assignments->count() > 0 ? round(($attemptedKeys->count() / $assignments->count()) * 100) : 0,
            ],
            'performance' => score_performance($attempts, $passing),
            'score_distribution' => score_distribution($attempts),
        ];
    }

    public function assessments(Admin $admin, array $data): array
    {
        $validated = $this->validateFilters($data);
        $passing = passing_percentage($validated);

        $assessments = $this->scopedAssessments($admin, $validated);
        $assessmentIds = $assessments->pluck('id');

        $attempts = $this->scopedAttempts($assessmentIds, $validated, false)->groupBy('assessment_id');
        $assignments = $this->scopedAssignments($assessmentIds, $validated)->groupBy('assessment_id');

        $questionCounts = AssessmentQuestion::whereIn('assessment_id', $assessmentIds)
            ->select('assessment_id', DB::raw('COUNT(*) as total'))->groupBy('assessment_id')->pluck('total', 'assessment_id');

        $batchCounts = Batch::whereIn('assessment_id', $assessmentIds)
            ->select('assessment_id', DB::raw('COUNT(*) as total'))->groupBy('assessment_id')->pluck('total', 'assessment_id');

        $rows = $assessments->map(function ($assessment) use ($attempts, $assignments, $questionCounts, $batchCounts, $passing) {
            $own = $attempts->get($assessment->id, collect());
            $submitted = $own->filter(fn ($attempt) => $attempt->submitted_at !== null);

            return [
                'assessment_id' => $assessment->id,
                'title' => $assessment->title,
                'type' => $assessment->type?->slug,
                'difficulty_level' => $assessment->difficulty_level,
                'is_active' => (bool) $assessment->is_active,
                'scheduling_type' => $assessment->scheduling_type,
                'questions' => (int) ($questionCounts[$assessment->id] ?? 0),
                'batches' => (int) ($batchCounts[$assessment->id] ?? 0),
                'assigned' => $assignments->get($assessment->id, collect())->count(),
                'attempts' => $own->count(),
                'in_progress' => $own->count() - $submitted->count(),
                'submitted' => $submitted->count(),
                'pending_evaluation' => $submitted->where('score', 'Pending Evaluation')->count(),
                ...score_performance($own, $passing),
            ];
        })->sortByDesc('attempts')->values();

        return ['filters' => applied_filters($validated), 'passing_percentage' => $passing, ...paginate_rows($rows, $validated)];
    }

    public function batches(Admin $admin, array $data): array
    {
        $validated = $this->validateFilters($data);
        $passing = passing_percentage($validated);

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = $this->scopedAssessments($admin, $validated);
        $assessmentIds = $assessments->pluck('id');
        $assessmentsById = $assessments->keyBy('id');

        $batchQuery = Batch::whereIn('assessment_id', $assessmentIds)->batchWiseOnly();

        if (!empty($validated['batch_id'])) {
            $batchQuery->where('id', $validated['batch_id']);
        }

        $batches = $batchQuery->get();

        $attempts = $this->scopedAttempts($assessmentIds, $validated, false)->groupBy('batch_id');
        $assignments = $this->scopedAssignments($assessmentIds, $validated)->groupBy('batch_id');

        $rows = $batches->map(function ($batch) use ($assessmentsById, $attempts, $assignments, $passing, $today, $nowTime) {
            $assessment = $assessmentsById->get($batch->assessment_id);
            $own = $attempts->get($batch->id, collect());
            $submitted = $own->filter(fn ($attempt) => $attempt->submitted_at !== null);
            $assigned = $assignments->get($batch->id, collect())->count();

            return [
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'assessment_id' => $batch->assessment_id,
                'assessment_title' => $assessment?->title,
                'status' => $assessment ? batch_status($assessment, $batch, $today, $nowTime) : null,
                'publish_date' => $batch->publish_date,
                'start_time' => $batch->start_time,
                'end_time' => $batch->end_time,
                'expiry_date' => $batch->expiry_date,
                'duration_minutes' => $batch->duration_minutes,
                'capacity' => $batch->capacity,
                'assigned' => $assigned,
                'attempts' => $own->count(),
                'submitted' => $submitted->count(),
                'participation_rate' => $assigned > 0 ? round(($own->count() / $assigned) * 100) : 0,
                ...score_performance($own, $passing),
            ];
        })->sortByDesc('attempts')->values();

        return ['filters' => applied_filters($validated), 'passing_percentage' => $passing, ...paginate_rows($rows, $validated)];
    }

    public function users(Admin $admin, array $data): array
    {
        $validated = $this->validateFilters($data);
        $passing = passing_percentage($validated);

        $assessments = $this->scopedAssessments($admin, $validated);
        $assessmentIds = $assessments->pluck('id');

        $assignments = $this->scopedAssignments($assessmentIds, $validated);
        $attempts = $this->scopedAttempts($assessmentIds, $validated)->groupBy('user_id');

        $userIds = $assignments->pluck('user_id')->merge($attempts->keys())->unique()->values();

        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        $assignmentsByUser = $assignments->groupBy('user_id');

        $rows = $userIds->map(function ($userId) use ($users, $assignmentsByUser, $attempts, $passing) {
            $user = $users->get($userId);
            $own = $attempts->get($userId, collect());
            $submitted = $own->filter(fn ($attempt) => $attempt->submitted_at !== null);

            return [
                'user_id' => $userId,
                'name' => $user?->name,
                'email' => $user?->email,
                'assigned' => $assignmentsByUser->get($userId, collect())->count(),
                'attempts' => $own->count(),
                'in_progress' => $own->count() - $submitted->count(),
                'submitted' => $submitted->count(),
                'pending_evaluation' => $submitted->where('score', 'Pending Evaluation')->count(),
                ...score_performance($own, $passing),
            ];
        })->sortByDesc('average_percentage')->values();

        return ['filters' => applied_filters($validated), 'passing_percentage' => $passing, ...paginate_rows($rows, $validated)];
    }

    public function questions(Admin $admin, array $data): array
    {
        $validated = $this->validateFilters($data);

        $assessments = $this->scopedAssessments($admin, $validated);
        $assessmentIds = $assessments->pluck('id');
        $assessmentsById = $assessments->keyBy('id');

        $attemptIds = $this->scopedAttempts($assessmentIds, $validated, false)->pluck('id');

        $stats = DB::table('assessment_answers')
            ->whereIn('attempt_id', $attemptIds)
            ->whereNull('deleted_at')
            ->select(
                'question_id',
                DB::raw('COUNT(*) as answered'),
                DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct'),
                DB::raw('SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) as wrong'),
                DB::raw('SUM(CASE WHEN is_correct IS NULL THEN 1 ELSE 0 END) as ungraded')
            )
            ->groupBy('question_id')
            ->get()
            ->keyBy('question_id');

        $questions = AssessmentQuestion::whereIn('assessment_id', $assessmentIds)->orderBy('assessment_id')->orderBy('order')->get();

        $rows = $questions->map(function ($question) use ($stats, $assessmentsById) {
            $stat = $stats->get($question->id);

            $answered = (int) ($stat->answered ?? 0);
            $correct = (int) ($stat->correct ?? 0);
            $wrong = (int) ($stat->wrong ?? 0);
            $ungraded = (int) ($stat->ungraded ?? 0);

            $graded = $correct + $wrong;
            $accuracy = $graded > 0 ? round(($correct / $graded) * 100) : null;

            $difficulty = $accuracy === null ? 'not_attempted' : ($accuracy >= 80 ? 'easy' : ($accuracy >= 50 ? 'medium' : 'hard'));

            return [
                'question_id' => $question->id,
                'assessment_id' => $question->assessment_id,
                'assessment_title' => $assessmentsById->get($question->assessment_id)?->title,
                'question_text' => $question->question_text,
                'type' => $question->type,
                'order' => $question->order,
                'answered' => $answered,
                'correct' => $correct,
                'wrong' => $wrong,
                'ungraded' => $ungraded,
                'accuracy' => $accuracy,
                'difficulty' => $difficulty,
            ];
        })->sortBy(fn ($row) => $row['accuracy'] ?? 101)->values();

        return ['filters' => applied_filters($validated), ...paginate_rows($rows, $validated)];
    }

    public function attempts(Admin $admin, array $data): array
    {
        $validated = $this->validateFilters($data);
        $passing = passing_percentage($validated);

        $assessments = $this->scopedAssessments($admin, $validated);
        $assessmentIds = $assessments->pluck('id');

        $attempts = $this->scopedAttempts($assessmentIds, $validated);
        $batches = Batch::whereIn('assessment_id', $assessmentIds)->get()->keyBy('id');

        $rows = $attempts->sortByDesc('id')->map(function ($attempt) use ($batches, $passing) {
            $result = attempt_result($attempt);
            $percentage = $result['percentage'] ?? null;
            $batch = $batches->get($attempt->batch_id);
            $hideBatch = is_implicit_batch($attempt->assessment, $batch);

            return [
                'attempt_id' => $attempt->id,
                'assessment_id' => $attempt->assessment_id,
                'assessment_title' => $attempt->assessment?->title,
                'batch_id' => $hideBatch ? null : $attempt->batch_id,
                'batch_name' => $hideBatch ? null : $batch?->name,
                'user_id' => $attempt->user_id,
                'user_name' => $attempt->user?->name,
                'user_email' => $attempt->user?->email,
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

        return ['filters' => applied_filters($validated), 'passing_percentage' => $passing, ...paginate_rows($rows, $validated)];
    }
}
