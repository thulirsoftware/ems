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
    public function start(User $user, $assessmentId): array
    {
        $assignment = AssessmentAssignment::where('assessment_id', $assessmentId)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $assessment = Assessment::where('id', $assessmentId)
            ->where('is_active', true)
            ->firstOrFail();

        $batch = Batch::find($assignment->batch_id);

        if (!$batch) {
            abort(422, 'Batch not found');
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

        $existing = AssessmentAttempt::where('assessment_id', $assessmentId)
            ->where('user_id', $user->id)
            ->where('batch_id', $assignment->batch_id)
            ->first();

        $hideBatch = is_implicit_batch($assessment, $batch);

        if ($existing) {
            if (!$existing->submitted_at) {
                return [
                    'status' => 200,
                    'message' => 'Resume your current attempt',
                    'attempt' => [...$existing->toArray(), 'batch_id' => $hideBatch ? null : $existing->batch_id],
                ];
            }

            abort(403, 'You have already completed this exam');
        }

        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessmentId,
            'user_id' => $user->id,
            'batch_id' => $assignment->batch_id,
            'started_at' => app_now()->toTimeString(),
        ]);

        return [
            'status' => 201,
            'message' => 'Assessment started',
            'attempt' => [...$attempt->toArray(), 'batch_id' => $hideBatch ? null : $attempt->batch_id],
        ];
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
        return [
            'score' => 'Pending Evaluation',
            'correct' => 0,
            'wrong' => 0,
            'total' => $attempt->assessment->questions()->count(),
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

        $ordered = $attempt->question_order ?? [];
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
