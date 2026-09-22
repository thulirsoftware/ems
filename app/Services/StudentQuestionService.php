<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\Batch;
use App\Models\User;

class StudentQuestionService
{
    public function index(User $user, $assessmentId)
    {
        $assignment = AssessmentAssignment::where('assessment_id', $assessmentId)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $attempt = AssessmentAttempt::where('assessment_id', $assessmentId)
            ->where('user_id', $user->id)
            ->where('batch_id', $assignment->batch_id)
            ->firstOrFail();

        $assessment = Assessment::findOrFail($assessmentId);

        $batch = Batch::find($assignment->batch_id);

        if (!$batch) {
            abort(422, 'Batch not found');
        }

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
            $isRunning = app_now()->lte(flexible_attempt_deadline($attempt, $batch));
        } else {
            $isRunning = $batch->publish_date == $today && $nowTime >= $batch->start_time && $nowTime <= $batch->end_time;
        }

        if (!$isRunning) {
            abort(403, 'Assessment not running');
        }

        if (!$attempt->question_order) {
            $questions = AssessmentQuestion::where('assessment_id', $assessmentId)->get();

            $ordered = $assessment->shuffle
                ? $questions->shuffle()->pluck('id')->toArray()
                : $questions->sortBy('order')->pluck('id')->toArray();

            $attempt->update(['question_order' => $ordered]);
        } else {
            $ordered = $attempt->question_order;
        }

        return AssessmentQuestion::whereIn('id', $ordered)
            ->with(['choices' => fn ($q) => $q->select('id', 'question_id', 'option', 'order')])
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $ordered))
            ->values();
    }
}
