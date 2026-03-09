<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use Illuminate\Http\Request;

class AdminResultController extends Controller
{
    // Helper to fetch assessment owned by admin
    private function getAdminAssessment($adminId, $assessmentId)
    {
        return Assessment::where('admin_id', $adminId)
            ->where('id', $assessmentId)
            ->with('type')
            ->firstOrFail();
    }

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

        $assessment = $this->getAdminAssessment($admin->id, $assessment_id);

        $assignments = AssessmentAssignment::where('assessment_id', $assessment_id)
            ->with('user:id,name')
            ->get();

        $attempts = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->get()
            ->keyBy('user_id');

        $users = $assignments->map(function ($assignment) use ($attempts) {

            $attempt = $attempts->get($assignment->user->id);

            return [
                'user_id' => $assignment->user->id,
                'name' => $assignment->user->name,
                'attempted' => $attempt && $attempt->submitted_at !== null,
                'grade_pending' => $attempt && $attempt->score === 'Pending Evaluation',
            ];
        });

        return response()->json($users);
    }

    // Get answers for admin grading
    public function userAnswersForGrading(Request $request, $assessment_id, $user_id)
    {
        $admin = $request->user('admins');

        $assessment = $this->getAdminAssessment($admin->id, $assessment_id);

        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user_id)
            ->with('answers')
            ->firstOrFail();

        if (!$attempt->submitted_at) {
            return response()->json([
                'message' => 'User has not submitted the assessment'
            ], 400);
        }

        if ($attempt->score !== 'Pending Evaluation') {
            return response()->json([
                'message' => 'Assessment already evaluated'
            ], 400);
        }

        $questions = $assessment->questions()
            ->with('choices')
            ->orderBy('order')
            ->get();

        $answers = $attempt->answers->keyBy('question_id');

        $data = $questions->map(function ($question) use ($answers, $assessment) {

            $answer = $answers->get($question->id);

            return [
                'question_id' => $question->id,
                'type' => $assessment->type->slug,
                'question_text' => $question->question_text,
                'options' => $question->choices->map(function ($choice) {
                    return [
                        'id' => $choice->id,
                        'option' => $choice->option
                    ];
                })->values(),
                'user_answer' => $answer?->answer,
                'is_correct' => $answer?->is_correct
            ];
        });

        return response()->json([
            'assessment_id' => $assessment_id,
            'user_id' => $user_id,
            'questions' => $data
        ]);
    }

    // Admin grades a specific answer
    public function gradeAnswer(Request $request, $assessment_id, $user_id, $question_id)
    {
        $admin = $request->user('admins');

        $validated = $request->validate([
            'is_correct' => 'required|boolean'
        ]);

        $assessment = $this->getAdminAssessment($admin->id, $assessment_id);

        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user_id)
            ->with('answers')
            ->firstOrFail();

        if (!$attempt->submitted_at) {
            return response()->json([
                'message' => 'Assessment not submitted yet'
            ], 400);
        }

        if ($attempt->score !== 'Pending Evaluation') {
            return response()->json([
                'message' => 'Assessment already evaluated'
            ], 400);
        }

        if ($assessment->type->slug === 'mcq') {
            return response()->json([
                'message' => 'MCQ assessments are auto evaluated'
            ], 400);
        }

        $answer = $attempt->answers()->firstOrCreate(
            ['question_id' => $question_id],
            ['answer' => null]
        );

        $answer->update([
            'is_correct' => $validated['is_correct']
        ]);

        $totalQuestions = $assessment->questions()->count();

        $gradedCount = $attempt->answers()
            ->whereNotNull('is_correct')
            ->count();

        if ($gradedCount === $totalQuestions) {

            $correct = $attempt->answers()->where('is_correct', true)->count();
            $wrong = $attempt->answers()->where('is_correct', false)->count();

            if ($assessment->has_negative) {
                $scoreValue = $correct - ($wrong * $assessment->negative_marks);
            } else {
                $scoreValue = $correct;
            }

            if ($scoreValue < 0) {
                $scoreValue = 0;
            }

            $attempt->update([
                'score' => $scoreValue . '/' . $totalQuestions
            ]);

            return response()->json([
                'message' => 'Answer graded. Final score calculated.',
                'score' => $attempt->score
            ]);
        }

        return response()->json([
            'message' => 'Answer graded successfully',
            'graded' => $gradedCount,
            'total' => $totalQuestions
        ]);
    }

    // 3. Final evaluated result
    public function userResult(Request $request, $assessment_id, $user_id)
    {
        $admin = $request->user('admins');

        $assessment = $this->getAdminAssessment($admin->id, $assessment_id);

        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user_id)
            ->with('answers')
            ->first();

        if (!$attempt || !$attempt->submitted_at) {
            return response()->json([
                'message' => 'User has not submitted the assessment'
            ], 400);
        }

        if ($attempt->score === 'Pending Evaluation') {
            return response()->json([
                'message' => 'Assessment results are not available yet'
            ], 403);
        }

        $questions = $assessment->questions()
            ->with('choices')
            ->orderBy('order')
            ->get();

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
                'options' => $question->choices->map(function ($choice) {
                    return [
                        'id' => $choice->id,
                        'option' => $choice->option,
                    ];
                })->values(),
                'user_answer' => $answer?->answer,
                'is_correct' => $answer?->is_correct,
            ];
        });

        $totalQuestions = $questions->count();

        $scoreParts = explode('/', $attempt->score);
        $scoreValue = (int) ($scoreParts[0] ?? 0);

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

    public function rankList(Request $request, $assessment_id)
    {
        $admin = $request->user('admins');

        $assessment = $this->getAdminAssessment($admin->id, $assessment_id);

        // Check if any submitted attempt is still pending evaluation
        $pendingExists = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->whereNotNull('submitted_at')
            ->where('score', 'Pending Evaluation')
            ->exists();

        if ($pendingExists) {
            return response()->json([
                'message' => 'Rank list cannot be generated until all assessments are evaluated'
            ], 400);
        }

        $attempts = AssessmentAttempt::with('user:id,name')
            ->where('assessment_id', $assessment_id)
            ->whereNotNull('submitted_at')
            ->get()
            ->map(function ($attempt) {

                $scoreParts = explode('/', $attempt->score);
                $scoreValue = (int) ($scoreParts[0] ?? 0);
                $total = (int) ($scoreParts[1] ?? 0);

                return [
                    'user_id' => $attempt->user->id,
                    'name' => $attempt->user->name,
                    'score' => $scoreValue,
                    'total_marks' => $total
                ];
            })
            ->sortByDesc('score')
            ->values()
            ->map(function ($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            });

        return response()->json($attempts);
    }
}
