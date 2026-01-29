<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentChoice;
use Illuminate\Http\Request;

class AssessmentAttemptController extends Controller
{
    // Start assessment (create attempt)
    public function start(Request $request, $assessment_id)
    {
        $user = $request->user('users');

        // Ensure assessment is assigned to this user
        AssessmentAssignment::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $assessment = Assessment::where('id', $assessment_id)
            ->where('is_active', true)
            ->firstOrFail();

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        // Ensure assessment is running
        if (
            $assessment->publish_date !== $today ||
            $nowTime < $assessment->start_time ||
            $nowTime > $assessment->end_time
        ) {
            return response()->json([
                'message' => 'Assessment is not currently available'
            ], 403);
        }

        // Prevent multiple attempts
        $existing = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Assessment already started',
                'attempt' => $existing
            ], 200);
        }

        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessment_id,
            'user_id' => $user->id,
            'started_at' => app_now()->toTimeString(),
        ]);

        return response()->json([
            'message' => 'Assessment started',
            'attempt' => $attempt
        ], 201);
    }

    // Submit assessment (finish attempt)
    public function submit(Request $request, $assessment_id)
    {
        $user = $request->user('users');

        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->with('answers')
            ->firstOrFail();

        if ($attempt->submitted_at) {
            return response()->json([
                'message' => 'Assessment already submitted'
            ], 400);
        }

        $assessment = Assessment::with('type')->findOrFail($assessment_id);
        $type = $assessment->type->slug;

        switch ($type) {
            case 'mcq':
                $result = $this->evaluateMcq($attempt);
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
        $totalQuestions = $attempt->answers->count();
        $correctCount = 0;

        foreach ($attempt->answers as $answer) {

            $choiceId = $answer->answer['choice_id'] ?? null;

            if (!$choiceId) {
                $answer->update(['is_correct' => false]);
                continue;
            }

            $choice = AssessmentChoice::find($choiceId);

            if ($choice && $choice->is_correct) {
                $correctCount++;
                $answer->update(['is_correct' => true]);
            } else {
                $answer->update(['is_correct' => false]);
            }
        }

        $score = $correctCount . '/' . $totalQuestions;

        return [
            'score' => $score,
            'correct' => $correctCount,
            'total' => $totalQuestions,
        ];
    }

    public function result(Request $request, $assessment_id)
    {
        $user = $request->user('users');

        $pageSize = $request->get('page_size', 10);

        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!$attempt->submitted_at) {
            return response()->json([
                'message' => 'Assessment not submitted yet'
            ], 400);
        }

        $answers = AssessmentAnswer::where('attempt_id', $attempt->id)
            ->with('question.choices')
            ->paginate($pageSize);

        $questions = $answers->getCollection()->map(function ($answer) {

            $yourChoice = $answer->question->choices
                ->firstWhere('id', $answer->answer['choice_id'] ?? null);

            $correctChoice = $answer->question->choices
                ->firstWhere('is_correct', true);

            return [
                'question_id' => $answer->question_id,
                'question' => $answer->question->question_text,
                'your_choice' => $yourChoice?->option,
                'correct_choice' => $correctChoice?->option,
                'is_correct' => $answer->is_correct,
            ];
        });

        return response()->json([
            'assessment_id' => $assessment_id,
            'score' => $attempt->score,
            'data' => $questions,
            'current_page' => $answers->currentPage(),
            'total_pages' => $answers->lastPage()
        ]);
    }
}
