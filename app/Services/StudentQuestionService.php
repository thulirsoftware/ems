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
            ->with('answers')
            ->firstOrFail();

        $assessment = Assessment::findOrFail($assessmentId);

        $batch = Batch::find($assignment->batch_id);

        if (!$batch) {
            abort(422, 'Batch not found');
        }

        // An unsubmitted attempt stays reachable after its deadline so the
        // student can come back (lost connection, closed tab) and submit —
        // answering itself is refused by StudentAnswerService.
        if ($attempt->submitted_at) {
            abort(403, 'Assessment already submitted');
        }

        if (!$attempt->question_order) {
            $ordered = resolve_question_order($assessment);

            $attempt->update(['question_order' => $ordered]);
        } else {
            $ordered = $attempt->question_order;
        }

        $answers = $attempt->answers->keyBy('question_id');

        return AssessmentQuestion::whereIn('id', $ordered)
            ->with(['choices' => fn ($q) => $q->select('id', 'question_id', 'option', 'order')])
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $ordered))
            ->values()
            // what the student already saved, so a resumed attempt shows it:
            // the choice id for MCQ, the text for descriptive
            ->map(function ($question) use ($answers) {
                $saved = $answers->get($question->id)?->answer;
                $question->setAttribute('saved_answer', $saved['choice_id'] ?? $saved['text'] ?? null);

                return $question;
            });
    }
}
