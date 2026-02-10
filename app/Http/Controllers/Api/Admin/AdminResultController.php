<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use Illuminate\Http\Request;

class AdminResultController extends Controller
{
    // 1. Get finished assessments
    public function finishedAssessments(Request $request)
    {
        $admin = $request->user('admins');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = Assessment::where('admin_id', $admin->id)
            ->where('is_active', true)
            ->where(function ($q) use ($today, $nowTime) {
                $q->whereDate('publish_date', '<', $today)
                    ->orWhere(function ($q2) use ($today, $nowTime) {
                        $q2->whereDate('publish_date', $today)
                            ->whereTime('end_time', '<', $nowTime);
                    });
            })
            ->latest()
            ->get();

        return response()->json($assessments);
    }

    // 2. Assigned users with attempt flag
    public function usersByAssessment(Request $request, $assessment_id)
    {
        $admin = $request->user('admins');

        $assessment = Assessment::where('admin_id', $admin->id)
            ->where('id', $assessment_id)
            ->first();

        if (!$assessment) {
            return response()->json([
                'message' => 'Assessment not found or you do not have access'
            ], 404);
        }

        $assignments = AssessmentAssignment::where('assessment_id', $assessment_id)
            ->with('user:id,name')
            ->get();

        $attemptedUserIds = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->pluck('user_id')
            ->toArray();

        $users = $assignments->map(function ($assignment) use ($attemptedUserIds) {
            return [
                'user_id' => $assignment->user->id,
                'name' => $assignment->user->name,
                'attempted' => in_array($assignment->user->id, $attemptedUserIds),
            ];
        });

        return response()->json($users);
    }

    // 3. Result of a specific user
    public function userResult(Request $request, $assessment_id, $user_id)
    {
        $admin = $request->user('admins');

        $assessment = Assessment::where('admin_id', $admin->id)
            ->where('id', $assessment_id)
            ->first();

        if (!$assessment) {
            return response()->json([
                'message' => 'Assessment not found or you do not have access'
            ], 404);
        }

        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user_id)
            ->with('answers')
            ->first();

        if (!$attempt || !$attempt->submitted_at) {
            return response()->json([
                'message' => 'User has not submitted the assessment'
            ], 400);
        }

        // Load all questions with choices
        $questions = $assessment->questions()->with('choices')->get();

        $answers = $attempt->answers->keyBy('question_id');

        $correct = 0;
        $wrong = 0;
        $unanswered = 0;

        $questionData = $questions->map(function ($question) use ($answers, &$correct, &$wrong, &$unanswered) {

            $answer = $answers->get($question->id);
            $userOptionId = $answer->answer['choice_id'] ?? null;

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
                'options' => $question->choices->map(function ($choice) {
                    return [
                        'id' => $choice->id,
                        'option' => $choice->option,
                    ];
                })->values(),
                'correct_option_id' => $correctOptionId,
                'user_option_id' => $userOptionId,
            ];
        });

        $totalQuestions = $questions->count();

        $scoreValue = (int) explode('/', $attempt->score)[0];

        $percentage = $totalQuestions > 0
            ? round(($scoreValue / $totalQuestions) * 100)
            : 0;

        return response()->json([
            'assessment_id' => $assessment_id,
            'user_id' => $user_id,
            'score' => $scoreValue,
            'total_marks' => $totalQuestions,
            'percentage' => $percentage,
            'correct' => $correct,
            'wrong' => $wrong,
            'unanswered' => $unanswered,
            'questions' => $questionData,
        ]);
    }
}
