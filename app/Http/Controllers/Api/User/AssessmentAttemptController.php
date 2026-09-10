<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentChoice;
use App\Models\Batch;
use App\Models\AssessmentQuestion;
use Illuminate\Http\Request;

class AssessmentAttemptController extends Controller
{
    // Start assessment
    public function start(Request $request, $assessment_id)
    {
        $user = $request->user('users');

        $assignment = AssessmentAssignment::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $assessment = Assessment::where('id', $assessment_id)
            ->where('is_active', true)
            ->firstOrFail();

        $batch = Batch::find($assignment->batch_id);

        if (!$batch) {
            return response()->json([
                'message' => 'Batch not found'
            ], 422);
        }

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        // Strict timing only for starting
        $isAvailable = false;

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {

            $isAvailable =
                (!$batch->publish_date || $batch->publish_date <= $today) &&
                (!$batch->expiry_date || $batch->expiry_date >= $today);

        } else {

            $isAvailable =
                $batch->publish_date == $today &&
                $nowTime >= $batch->start_time &&
                $nowTime <= $batch->end_time;
        }

        if (!$isAvailable) {
            return response()->json([
                'message' => 'Assessment is not currently available'
            ], 403);
        }

        $existing = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->where('batch_id', $assignment->batch_id)
            ->first();

        $hideBatch = is_implicit_batch($assessment, $batch);

        if ($existing) {

            if (!$existing->submitted_at) {
                return response()->json([
                    'message' => 'Resume your current attempt',
                    'attempt' => [
                        ...$existing->toArray(),
                        'batch_id' => $hideBatch ? null : $existing->batch_id,
                    ],
                ], 200);
            }

            return response()->json([
                'message' => 'You have already completed this exam'
            ], 403);
        }

        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessment_id,
            'user_id' => $user->id,
            'batch_id' => $assignment->batch_id,
            'started_at' => app_now()->toTimeString(),
        ]);

        return response()->json([
            'message' => 'Assessment started',
            'attempt' => [
                ...$attempt->toArray(),
                'batch_id' => $hideBatch ? null : $attempt->batch_id,
            ],
        ], 201);
    }

    // Submit assessment
    public function submit(Request $request, $assessment_id)
    {
        $user = $request->user('users');

        $assignment = AssessmentAssignment::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->where('batch_id', $assignment->batch_id)
            ->with(['answers', 'assessment.type'])
            ->firstOrFail();

        if ($attempt->submitted_at) {
            return response()->json([
                'message' => 'Assessment already submitted'
            ], 400);
        }

        $type = $attempt->assessment->type->slug;

        switch ($type) {
            case 'mcq':
                $result = $this->evaluateMcq($attempt);
                break;

            case 'descriptive':
                $result = $this->submitManualAssessment($attempt);
                break;

            default:
                return response()->json([
                    'message' => 'Unsupported assessment type'
                ], 422);
        }

        $attempt->update([
            'submitted_at' => app_now()->toTimeString(),
            'score' => $result['score'],
        ]);

        return response()->json([
            'message' => 'Assessment submitted successfully',
            'score' => $result['score'],
            'correct' => $result['correct'],
            'total' => $result['total'],
        ]);
    }

    private function evaluateMcq(AssessmentAttempt $attempt)
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

        if ($assessment->has_negative) {
            $finalScoreValue = $correctCount - ($wrongCount * $assessment->negative_marks);
        } else {
            $finalScoreValue = $correctCount;
        }

        if ($finalScoreValue < 0) {
            $finalScoreValue = 0;
        }

        $score = $finalScoreValue . '/' . $totalQuestions;

        return [
            'score' => $score,
            'correct' => $correctCount,
            'wrong' => $wrongCount,
            'total' => $totalQuestions,
        ];
    }

    private function submitManualAssessment(AssessmentAttempt $attempt)
    {
        $totalQuestions = $attempt->assessment->questions()->count();

        return [
            'score' => 'Pending Evaluation',
            'correct' => 0,
            'wrong' => 0,
            'total' => $totalQuestions,
        ];
    }

    public function result(Request $request, $assessment_id)
    {
        $user = $request->user('users');

        $assignment = AssessmentAssignment::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->where('batch_id', $assignment->batch_id)
            ->with('answers')
            ->firstOrFail();

        if (!$attempt->submitted_at) {
            return response()->json([
                'message' => 'Assessment not submitted yet'
            ], 400);
        }

        if ($attempt->score === 'Pending Evaluation') {
            return response()->json([
                'message' => 'Your assessment is under evaluation'
            ], 403);
        }

        $ordered = $attempt->question_order ?? [];
        $totalQuestions = count($ordered);

        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->get('page_size', 10)));
        $offset = ($page - 1) * $pageSize;

        $paginatedIds = array_slice($ordered, $offset, $pageSize);

        $questions = AssessmentQuestion::whereIn('id', $paginatedIds)
            ->with('choices')
            ->get()
            ->sortBy(fn($q) => array_search($q->id, $paginatedIds))
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
                'options' => $question->choices->map(fn($c) => [
                    'id' => $c->id,
                    'option' => $c->option,
                ])->values(),
                'correct_option_id' => $correctOptionId,
                'user_option_id' => $userOptionId,
            ];
        });

        // Use the same score parsing as reports/dashboards (handles the
        // fractional scores that negative marking can produce) instead of
        // truncating to an int locally.
        $parsed = parse_score($attempt->score);
        $scoreValue = $parsed['score'] ?? 0;
        $percentage = $totalQuestions > 0
            ? round(($scoreValue / $totalQuestions) * 100)
            : 0;

        return response()->json([
            'score' => $scoreValue,
            'total_marks' => $totalQuestions,
            'percentage' => $percentage,
            'correct' => $correct,
            'wrong' => $wrong,
            'unanswered' => $unanswered,
            'questions' => $questionData,
            'current_page' => $page,
            'total_pages' => (int) ceil($totalQuestions / $pageSize),
        ]);
    }
}