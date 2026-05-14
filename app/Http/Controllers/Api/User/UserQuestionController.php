<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use Illuminate\Http\Request;

class UserQuestionController extends Controller
{
    public function index(Request $request, $assessment_id)
    {
        $user = $request->user('users');

        // 🔥 Get latest assignment (re-exam safe)
        $assignment = \App\Models\AssessmentAssignment::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        // 🔥 Get correct attempt
        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->where('batch_id', $assignment->batch_id)
            ->firstOrFail();

        $assessment = Assessment::findOrFail($assessment_id);

        // 🔥 batch-based timing
        $batch = \App\Models\Batch::find($assignment->batch_id);

        if (!$batch) {
            return response()->json([
                'message' => 'Batch not found'
            ], 422);
        }

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $isRunning = false;

        if ($assessment->is_flexible) {

            if ($batch->duration_minutes) {

                $startedAt = app_now()
                    ->copy()
                    ->setTimeFromTimeString(
                        $attempt->started_at
                    );

                if ($startedAt->gt(app_now())) {
                    $startedAt->subDay();
                }

                $expiresAt = $startedAt
                    ->copy()
                    ->addMinutes(
                        $batch->duration_minutes
                    );

                $isRunning =
                    app_now()->lte($expiresAt);

            } else {

                $isRunning = true;
            }

        } else {

            $isRunning =
                $batch->publish_date == $today &&
                $nowTime >= $batch->start_time &&
                $nowTime <= $batch->end_time;
        }

        if (!$isRunning) {
            return response()->json([
                'message' => 'Assessment not running'
            ], 403);
        }

        // 🔥 generate order if not exists
        if (!$attempt->question_order) {

            $questions = AssessmentQuestion::where('assessment_id', $assessment_id)->get();

            if ($assessment->shuffle) {
                $ordered = $questions->shuffle()->pluck('id')->toArray();
            } else {
                $ordered = $questions->sortBy('order')->pluck('id')->toArray();
            }

            $attempt->update([
                'question_order' => $ordered
            ]);

        } else {
            $ordered = $attempt->question_order;
        }

        // 🔥 fetch in order
        $questions = AssessmentQuestion::whereIn('id', $ordered)
            ->with([
                'choices' => function ($q) {
                    $q->select('id', 'question_id', 'option', 'order');
                }
            ])
            ->get()
            ->sortBy(fn($q) => array_search($q->id, $ordered))
            ->values();

        return response()->json($questions);
    }
}
