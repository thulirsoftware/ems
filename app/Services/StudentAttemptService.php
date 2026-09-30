<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentChoice;
use App\Models\AssessmentQuestion;
use App\Models\Batch;
use App\Models\User;

class StudentAttemptService
{
    public function __construct(
        private ResultService $resultService
    ) {}

    public function start(User $user, $assessmentId): array
    {
        $assignment = AssessmentAssignment::where('assessment_id', $assessmentId)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $assessment = Assessment::findOrFail($assessmentId);

        $batch = Batch::find($assignment->batch_id);

        if (!$batch) {
            abort(422, 'Batch not found');
        }

        $hideBatch = is_implicit_batch($assessment, $batch);

        $existing = AssessmentAttempt::where('assessment_id', $assessmentId)
            ->where('user_id', $user->id)
            ->where('batch_id', $assignment->batch_id)
            ->first();

        // An attempt already started can always be resumed until it is
        // submitted — even after the window closed (answering is blocked then,
        // but the student must still be able to come back and submit).
        if ($existing) {
            return $this->resumeOrReject($existing, $assessment, $batch, $hideBatch);
        }

        if (!$assessment->is_active) {
            abort(404, 'The resource you are trying to access does not exist.');
        }

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
            $isAvailable = (!$batch->publish_date || $batch->publish_date <= $today) && (!$batch->expiry_date || $batch->expiry_date >= $today);
        } else {
            $isAvailable = $batch->publish_date == $today && $nowTime >= $batch->start_time && $nowTime <= $batch->end_time;
        }

        if (!$isAvailable) {
            abort(403, 'Assessment is not currently available');
        }

        // Nothing to answer would produce a meaningless 0/0 score that every
        // report treats as "never graded".
        if (!AssessmentQuestion::where('assessment_id', $assessment->id)->exists()) {
            abort(422, 'This assessment has no questions yet. Please contact your administrator.');
        }

        try {
            $attempt = AssessmentAttempt::create([
                'assessment_id' => $assessmentId,
                'user_id' => $user->id,
                'batch_id' => $assignment->batch_id,
                'started_at' => app_now()->toTimeString(),
                'question_order' => resolve_question_order($assessment),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Lost the race to a concurrent start() for the same
            // assessment/user/batch — someone else's insert won, so resume
            // (or reject) that one instead of erroring out.
            $existing = AssessmentAttempt::where('assessment_id', $assessmentId)
                ->where('user_id', $user->id)
                ->where('batch_id', $assignment->batch_id)
                ->firstOrFail();

            return $this->resumeOrReject($existing, $assessment, $batch, $hideBatch);
        }

        return [
            'status' => 201,
            'message' => 'Assessment started',
            'attempt' => [
                ...$attempt->toArray(),
                'batch_id' => $hideBatch ? null : $attempt->batch_id,
                ...attempt_state($assessment, $batch, $attempt),
            ],
        ];
    }

    private function resumeOrReject(AssessmentAttempt $existing, Assessment $assessment, Batch $batch, bool $hideBatch): array
    {
        if (!$existing->submitted_at) {
            $state = attempt_state($assessment, $batch, $existing);

            return [
                'status' => 200,
                'message' => $state['answering_closed']
                    ? 'Time is over — answering is closed. Submit your attempt to finish.'
                    : 'Resume your current attempt',
                'attempt' => [
                    ...$existing->toArray(),
                    'batch_id' => $hideBatch ? null : $existing->batch_id,
                    ...$state,
                ],
            ];
        }

        abort(403, 'You have already completed this exam');
    }

    public function submit(User $user, $assessmentId): array
    {
        $assignment = AssessmentAssignment::where('assessment_id', $assessmentId)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $attempt = AssessmentAttempt::where('assessment_id', $assessmentId)
            ->where('user_id', $user->id)
            ->where('batch_id', $assignment->batch_id)
            ->with(['answers', 'assessment.type'])
            ->firstOrFail();

        if ($attempt->submitted_at) {
            abort(400, 'Assessment already submitted');
        }

        $type = $attempt->assessment->type->slug;

        $result = match ($type) {
            'mcq' => $this->evaluateMcq($attempt),
            'descriptive' => $this->submitManualAssessment($attempt),
            default => null,
        };

        if ($result === null) {
            abort(422, 'Unsupported assessment type');
        }

        $attempt->update([
            'submitted_at' => app_now()->toTimeString(),
            'score' => $result['score'],
        ]);

        return $result;
    }

    private function evaluateMcq(AssessmentAttempt $attempt): array
    {
        $assessment = $attempt->assessment;

        $totalQuestions = $assessment->questions()->count();
        $correctCount = 0;
        $wrongCount = 0;

        foreach ($attempt->answers as $answer) {
            $choiceId = $answer->answer['choice_id'] ?? null;

            if (!$choiceId) {
                $wrongCount++;
                $answer->update(['is_correct' => false]);
                continue;
            }

            $choice = AssessmentChoice::find($choiceId);

            if ($choice && $choice->is_correct) {
                $correctCount++;
                $answer->update(['is_correct' => true]);
            } else {
                $wrongCount++;
                $answer->update(['is_correct' => false]);
            }
        }

        $finalScoreValue = $assessment->has_negative
            ? $correctCount - ($wrongCount * $assessment->negative_marks)
            : $correctCount;

        if ($finalScoreValue < 0) {
            $finalScoreValue = 0;
        }

        return [
            'score' => $finalScoreValue.'/'.$totalQuestions,
            'correct' => $correctCount,
            'wrong' => $wrongCount,
            'total' => $totalQuestions,
        ];
    }

    private function submitManualAssessment(AssessmentAttempt $attempt): array
    {
        $assessment = $attempt->assessment;
        $answers = $attempt->answers->keyBy('question_id');

        // A question left blank has nothing to evaluate — mark it incorrect
        // now so the administrator only grades real answers.
        foreach ($assessment->questions()->pluck('id') as $questionId) {
            $answer = $answers->get($questionId);

            if ($answer && !descriptive_answer_is_blank($answer->answer)) {
                continue;
            }

            $attempt->answers()->updateOrCreate(
                ['question_id' => $questionId],
                ['answer' => $answer?->answer, 'is_correct' => false]
            );
        }

        // Everything blank → nothing left to grade, so the score is final now.
        $final = $this->resultService->finalizeIfFullyGraded($assessment, $attempt);

        return [
            'score' => $final['final'] ? $final['score'] : 'Pending Evaluation',
            'correct' => 0,
            'wrong' => 0,
            'total' => $final['total'] ?? $assessment->questions()->count(),
        ];
    }

    public function result(User $user, $assessmentId, array $query): array
    {
        $assignment = AssessmentAssignment::where('assessment_id', $assessmentId)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $attempt = AssessmentAttempt::where('assessment_id', $assessmentId)
            ->where('user_id', $user->id)
            ->where('batch_id', $assignment->batch_id)
            ->with('answers')
            ->firstOrFail();

        if (!$attempt->submitted_at) {
            abort(400, 'Assessment not submitted yet');
        }

        if ($attempt->score === 'Pending Evaluation') {
            abort(403, 'Your assessment is under evaluation');
        }

        // The result reveals the correct answers — hold it back until the
        // window has closed for every candidate of this batch.
        $batch = Batch::find($attempt->batch_id);

        if (!results_released($attempt->assessment, $batch)) {
            $releaseAt = results_release_time($attempt->assessment, $batch);

            abort(403, 'Your answers are submitted. Results will be available after the exam closes on '.$releaseAt->format('d M Y, h:i A').'.');
        }

        $ordered = $attempt->question_order;

        if (empty($ordered)) {
            // Attempts created before question_order was seeded at start()
            // time (or reached here without ever hitting the question-list
            // endpoint) would otherwise report 0 total_marks / 0% despite a
            // correctly-scored attempt — self-heal by deriving it now.
            $ordered = resolve_question_order($attempt->assessment);
            $attempt->update(['question_order' => $ordered]);
        }

        $totalQuestions = count($ordered);

        $page = max(1, (int) ($query['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($query['page_size'] ?? 10)));
        $offset = ($page - 1) * $pageSize;

        $paginatedIds = array_slice($ordered, $offset, $pageSize);

        $questions = AssessmentQuestion::whereIn('id', $paginatedIds)
            ->with('choices')
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $paginatedIds))
            ->values();

        $answers = $attempt->answers->keyBy('question_id');

        $correct = 0;
        $wrong = 0;
        $unanswered = 0;

        $questionData = $questions->map(function ($question) use ($answers, &$correct, &$wrong, &$unanswered) {
            $answer = $answers->get($question->id);
            $userOptionId = $answer?->answer['choice_id'] ?? null;

            $correctOption = $question->choices->firstWhere('is_correct', true);
            $correctOptionId = $correctOption?->id;

            if (!$userOptionId) {
                $unanswered++;
            } elseif ($userOptionId == $correctOptionId) {
                $correct++;
            } else {
                $wrong++;
            }

            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'options' => $question->choices->map(fn ($c) => ['id' => $c->id, 'option' => $c->option])->values(),
                'correct_option_id' => $correctOptionId,
                'user_option_id' => $userOptionId,
            ];
        });

        $parsed = parse_score($attempt->score);
        $scoreValue = $parsed['score'] ?? 0;
        $percentage = $totalQuestions > 0 ? round(($scoreValue / $totalQuestions) * 100) : 0;

        return [
            'score' => $scoreValue,
            'total_marks' => $totalQuestions,
            'percentage' => $percentage,
            'correct' => $correct,
            'wrong' => $wrong,
            'unanswered' => $unanswered,
            'questions' => $questionData,
            'current_page' => $page,
            'total_pages' => (int) ceil($totalQuestions / $pageSize),
        ];
    }
}
