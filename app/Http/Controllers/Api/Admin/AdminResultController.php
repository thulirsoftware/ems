<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\Batch;
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

    // 1. Get finished assessments (batch-based)
    public function finishedAssessments(Request $request)
    {
        $admin = $request->user('admins');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = Assessment::where('admin_id', $admin->id)
            ->where('is_active', true)
            ->get();

        $batchesByAssessment = Batch::whereIn('assessment_id', $assessments->pluck('id'))
            ->get()
            ->groupBy('assessment_id');

        $result = [];

        foreach ($assessments as $assessment) {

            $batches = $batchesByAssessment->get($assessment->id, collect());

            foreach ($batches as $batch) {

                $isFinished = false;

                if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {

                    $isFinished = true;

                } else {

                    $isFinished =
                        $batch->publish_date < $today ||
                        (
                            $batch->publish_date == $today &&
                            $batch->end_time < $nowTime
                        );
                }

                if (!$isFinished) {
                    continue;
                }

                $result[] = [
                    ...$assessment->toArray(),
                    ...batch_schedule_fields($assessment, $batch),
                ];
            }
        }

        return response()->json($result);
    }

    // 2. Assigned users with attempt flag (latest per user)
    public function usersByAssessment(Request $request, $assessment_id)
    {
        $admin = $request->user('admins');

        $assessment = $this->getAdminAssessment($admin->id, $assessment_id);

        $result = resolve_batch($assessment, $request->query('batch_id'));

        if (isset($result['error'])) {
            return $result['error'];
        }

        $batch = $result['batch'];
        $batchId = $batch?->id;

        // latest assignment per user
        $assignments = AssessmentAssignment::where('assessment_id', $assessment_id)
            ->with('user:id,name')
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->values();

        $attempts = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('batch_id', $batchId)
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

    // 3. Get answers for admin grading (batch-aware)
    public function userAnswersForGrading(Request $request, $assessment_id, $user_id)
    {
        $admin = $request->user('admins');

        $assessment = $this->getAdminAssessment($admin->id, $assessment_id);

        $result = resolve_batch($assessment, $request->query('batch_id'));

        if (isset($result['error'])) {
            return $result['error'];
        }

        $batch = $result['batch'];
        $batchId = $batch?->id;

        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('batch_id', $batchId)
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
            'batch_id' => is_implicit_batch($assessment, $batch) ? null : $batchId,
            'user_id' => $user_id,
            'questions' => $data
        ]);
    }

    // 4. Admin grades a specific answer (batch-aware)
    public function gradeAnswer(Request $request, $assessment_id, $user_id, $question_id)
    {
        $admin = $request->user('admins');

        $validated = $request->validate([
            'is_correct' => 'required|boolean'
        ]);

        $assessment = $this->getAdminAssessment($admin->id, $assessment_id);

        $result = resolve_batch($assessment, $request->query('batch_id'));

        if (isset($result['error'])) {
            return $result['error'];
        }

        $batch = $result['batch'];
        $batchId = $batch?->id;

        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('batch_id', $batchId)
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

        // Ensure the question actually belongs to this assessment, otherwise
        // the graded-count-vs-total-questions check below can be thrown off.
        $assessment->questions()->where('id', $question_id)->firstOrFail();

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

    // 5. Final evaluated result (batch-aware)
    public function userResult(Request $request, $assessment_id, $user_id)
    {
        $admin = $request->user('admins');

        $assessment = $this->getAdminAssessment($admin->id, $assessment_id);

        $result = resolve_batch($assessment, $request->query('batch_id'));

        if (isset($result['error'])) {
            return $result['error'];
        }

        $batch = $result['batch'];
        $batchId = $batch?->id;

        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('batch_id', $batchId)
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

        // Same score parsing as reports/dashboards (handles the fractional
        // scores that negative marking can produce).
        $parsed = parse_score($attempt->score);
        $scoreValue = $parsed['score'] ?? 0;

        $percentage = $totalQuestions > 0
            ? round(($scoreValue / $totalQuestions) * 100)
            : 0;

        return response()->json([
            'assessment_id' => $assessment_id,
            'batch_id' => is_implicit_batch($assessment, $batch) ? null : $batchId,
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

    // 6. Rank list: evaluated attempts for an assessment, ranked by score
    // (batch-aware). This route existed with no backing method — every call
    // threw an uncaught "call to undefined method" error.
    public function rankList(Request $request, $assessment_id)
    {
        $admin = $request->user('admins');

        $assessment = $this->getAdminAssessment($admin->id, $assessment_id);

        $result = resolve_batch($assessment, $request->query('batch_id'));

        if (isset($result['error'])) {
            return $result['error'];
        }

        $batch = $result['batch'];
        $batchId = $batch?->id;

        $attempts = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('batch_id', $batchId)
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

        return response()->json([
            'assessment_id' => (int) $assessment_id,
            'batch_id' => is_implicit_batch($assessment, $batch) ? null : $batchId,
            'rank_list' => $rankList,
        ]);
    }
}