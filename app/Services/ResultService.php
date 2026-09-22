<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\Batch;
use Illuminate\Support\Facades\Validator;

class ResultService
{
    // $id/$assessmentId/$userId/$batchId are untyped — see the comment on
    // AssessmentService::findOwned() for why.
    private function ownedAssessment(Admin $admin, $id): Assessment
    {
        return Assessment::where('admin_id', $admin->id)->where('id', $id)->with('type')->firstOrFail();
    }

    private function resolveBatch(Assessment $assessment, $batchId)
    {
        $result = resolve_batch($assessment, $batchId);

        if (isset($result['error'])) {
            abort(422, $result['error']->getData()->message);
        }

        return $result['batch'];
    }

    public function finishedAssessments(Admin $admin): array
    {
        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = Assessment::where('admin_id', $admin->id)->where('is_active', true)->get();
        $batchesByAssessment = Batch::whereIn('assessment_id', $assessments->pluck('id'))->get()->groupBy('assessment_id');

        $result = [];

        foreach ($assessments as $assessment) {
            foreach ($batchesByAssessment->get($assessment->id, collect()) as $batch) {
                $isFinished = $assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE
                    ? true
                    : ($batch->publish_date < $today || ($batch->publish_date == $today && $batch->end_time < $nowTime));

                if ($isFinished) {
                    $result[] = [...$assessment->toArray(), ...batch_schedule_fields($assessment, $batch)];
                }
            }
        }

        return $result;
    }

    public function usersByAssessment(Admin $admin, $assessmentId, $batchId): array
    {
        $assessment = $this->ownedAssessment($admin, $assessmentId);
        $batch = $this->resolveBatch($assessment, $batchId);
        $resolvedBatchId = $batch?->id;

        $assignments = AssessmentAssignment::where('assessment_id', $assessment->id)
            ->with('user:id,name')
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->values();

        $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)->where('batch_id', $resolvedBatchId)->get()->keyBy('user_id');

        return $assignments->map(function ($assignment) use ($attempts) {
            $attempt = $attempts->get($assignment->user->id);

            return [
                'user_id' => $assignment->user->id,
                'name' => $assignment->user->name,
                'attempted' => $attempt && $attempt->submitted_at !== null,
                'grade_pending' => $attempt && $attempt->score === 'Pending Evaluation',
            ];
        })->all();
    }

    public function answersForGrading(Admin $admin, $assessmentId, $userId, $batchId): array
    {
        $assessment = $this->ownedAssessment($admin, $assessmentId);
        $batch = $this->resolveBatch($assessment, $batchId);
        $resolvedBatchId = $batch?->id;

        $attempt = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('batch_id', $resolvedBatchId)
            ->where('user_id', $userId)
            ->with('answers')
            ->firstOrFail();

        if (!$attempt->submitted_at) {
            abort(400, 'User has not submitted the assessment');
        }

        if ($attempt->score !== 'Pending Evaluation') {
            abort(400, 'Assessment already evaluated');
        }

        $questions = $assessment->questions()->with('choices')->orderBy('order')->get();
        $answers = $attempt->answers->keyBy('question_id');

        $data = $questions->map(fn ($question) => [
            'question_id' => $question->id,
            'type' => $assessment->type->slug,
            'question_text' => $question->question_text,
            'options' => $question->choices->map(fn ($choice) => ['id' => $choice->id, 'option' => $choice->option])->values(),
            'user_answer' => $answers->get($question->id)?->answer,
            'is_correct' => $answers->get($question->id)?->is_correct,
        ]);

        $hideBatch = is_implicit_batch($assessment, $batch);

        return [
            'assessment_id' => $assessment->id,
            'batch_id' => $hideBatch ? null : $resolvedBatchId,
            'user_id' => $userId,
            'questions' => $data,
        ];
    }

    public function gradeAnswer(Admin $admin, array $data): array
    {
        $validated = Validator::make($data, [
            'assessment_id' => 'required|integer',
            'user_id' => 'required|integer',
            'question_id' => 'required|integer',
            'is_correct' => 'required|boolean',
            'batch_id' => 'nullable|integer',
        ])->validate();

        $assessment = $this->ownedAssessment($admin, $validated['assessment_id']);
        $batch = $this->resolveBatch($assessment, $validated['batch_id'] ?? null);
        $resolvedBatchId = $batch?->id;

        $attempt = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('batch_id', $resolvedBatchId)
            ->where('user_id', $validated['user_id'])
            ->with('answers')
            ->firstOrFail();

        if (!$attempt->submitted_at) {
            abort(400, 'Assessment not submitted yet');
        }

        if ($attempt->score !== 'Pending Evaluation') {
            abort(400, 'Assessment already evaluated');
        }

        if ($assessment->type->slug === 'mcq') {
            abort(400, 'MCQ assessments are auto evaluated');
        }

        $assessment->questions()->where('id', $validated['question_id'])->firstOrFail();

        $answer = $attempt->answers()->firstOrCreate(['question_id' => $validated['question_id']], ['answer' => null]);
        $answer->update(['is_correct' => $validated['is_correct']]);

        $totalQuestions = $assessment->questions()->count();
        $gradedCount = $attempt->answers()->whereNotNull('is_correct')->count();

        if ($gradedCount === $totalQuestions) {
            $correct = $attempt->answers()->where('is_correct', true)->count();
            $wrong = $attempt->answers()->where('is_correct', false)->count();

            $scoreValue = $assessment->has_negative ? $correct - ($wrong * $assessment->negative_marks) : $correct;
            $scoreValue = max(0, $scoreValue);

            $attempt->update(['score' => $scoreValue.'/'.$totalQuestions]);

            return ['final' => true, 'score' => $attempt->score];
        }

        return ['final' => false, 'graded' => $gradedCount, 'total' => $totalQuestions];
    }

    public function userResult(Admin $admin, $assessmentId, $userId, $batchId): array
    {
        $assessment = $this->ownedAssessment($admin, $assessmentId);
        $batch = $this->resolveBatch($assessment, $batchId);
        $resolvedBatchId = $batch?->id;

        $attempt = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('batch_id', $resolvedBatchId)
            ->where('user_id', $userId)
            ->with('answers')
            ->first();

        if (!$attempt || !$attempt->submitted_at) {
            abort(400, 'User has not submitted the assessment');
        }

        if ($attempt->score === 'Pending Evaluation') {
            abort(403, 'Assessment results are not available yet');
        }

        $questions = $assessment->questions()->with('choices')->orderBy('order')->get();
        $answers = $attempt->answers->keyBy('question_id');

        $correct = 0;
        $wrong = 0;
        $unanswered = 0;

        $questionData = $questions->map(function ($question) use ($answers, &$correct, &$wrong, &$unanswered, $assessment) {
            $answer = $answers->get($question->id);

            if (!$answer) {
                $unanswered++;
            } elseif ($answer->is_correct === true) {
                $correct++;
            } elseif ($answer->is_correct === false) {
                $wrong++;
            }

            return [
                'id' => $question->id,
                'type' => $assessment->type->slug,
                'question_text' => $question->question_text,
                'options' => $question->choices->map(fn ($choice) => ['id' => $choice->id, 'option' => $choice->option])->values(),
                'user_answer' => $answer?->answer,
                'is_correct' => $answer?->is_correct,
            ];
        });

        $totalQuestions = $questions->count();
        $parsed = parse_score($attempt->score);
        $scoreValue = $parsed['score'] ?? 0;
        $percentage = $totalQuestions > 0 ? round(($scoreValue / $totalQuestions) * 100) : 0;

        $hideBatch = is_implicit_batch($assessment, $batch);

        return [
            'assessment_id' => $assessment->id,
            'batch_id' => $hideBatch ? null : $resolvedBatchId,
            'user_id' => $userId,
            'score' => $scoreValue,
            'total_marks' => $totalQuestions,
            'percentage' => $percentage,
            'correct' => $correct,
            'wrong' => $wrong,
            'unanswered' => $unanswered,
            'questions' => $questionData,
        ];
    }

    public function rankList(Admin $admin, $assessmentId, $batchId): array
    {
        $assessment = $this->ownedAssessment($admin, $assessmentId);
        $batch = $this->resolveBatch($assessment, $batchId);
        $resolvedBatchId = $batch?->id;

        $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('batch_id', $resolvedBatchId)
            ->whereNotNull('submitted_at')
            ->with('user:id,name')
            ->get();

        $rankList = $attempts
            ->map(function ($attempt) {
                $parsed = parse_score($attempt->score);

                if (!$parsed) {
                    return null;
                }

                return [
                    'user_id' => $attempt->user_id,
                    'name' => $attempt->user?->name,
                    'score' => $parsed['score'],
                    'total_marks' => $parsed['total'],
                    'percentage' => $parsed['percentage'],
                ];
            })
            ->filter()
            ->sortByDesc('percentage')
            ->values()
            ->map(function ($row, $index) {
                $row['rank'] = $index + 1;

                return $row;
            });

        $hideBatch = is_implicit_batch($assessment, $batch);

        return [
            'assessment_id' => $assessment->id,
            'batch_id' => $hideBatch ? null : $resolvedBatchId,
            'rank_list' => $rankList,
        ];
    }
}
