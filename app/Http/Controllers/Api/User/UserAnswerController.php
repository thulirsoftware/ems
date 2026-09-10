<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentChoice;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentQuestion;
use App\Models\Batch;
use Illuminate\Http\Request;

class UserAnswerController extends Controller
{
    private function handleMcq(array $data)
    {
        AssessmentChoice::where('id', $data['choice_id'])
            ->where('question_id', $data['question_id'])
            ->firstOrFail();

        return [
            'choice_id' => $data['choice_id']
        ];
    }

    private function handleDescriptive(array $data)
    {
        if (empty($data['answer'])) {
            abort(422, 'Answer is required');
        }

        return [
            'text' => $data['answer']
        ];
    }

    public function store(Request $request, $assessment_id)
    {
        $user = $request->user('users');

        $assessment = Assessment::with('type')
            ->where('id', $assessment_id)
            ->firstOrFail();

        $type = $assessment->type->slug;

        $rules = [
            'question_id' => 'required|exists:assessment_questions,id',
        ];

        if ($type === 'mcq') {
            $rules['choice_id'] = 'required|exists:assessment_choices,id';
        } else {
            $rules['choice_id'] = 'nullable|exists:assessment_choices,id';
        }

        $rules['answer'] = 'nullable|string';

        $validated = $request->validate($rules);

        // Get latest assignment (re-exam safe)
        $assignment = AssessmentAssignment::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        // Get correct attempt (batch-aware)
        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->where('batch_id', $assignment->batch_id)
            ->firstOrFail();

        if ($attempt->submitted_at) {
            return response()->json([
                'message' => 'Assessment already submitted'
            ], 403);
        }

        $batch = Batch::findOrFail(
            $assignment->batch_id
        );

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {

            // Capped at end_date so an attempt started near the end of the
            // allowed date range can't run past it, even if duration_minutes
            // would otherwise carry it further.
            if (app_now()->gt(flexible_attempt_deadline($attempt, $batch))) {
                return response()->json([
                    'message' => 'Assessment time expired'
                ], 403);
            }

        } else {

            $isExpired =
                $batch->publish_date < $today ||
                (
                    $batch->publish_date == $today &&
                    $nowTime > $batch->end_time
                );

            if ($isExpired) {
                return response()->json([
                    'message' => 'Assessment time expired'
                ], 403);
            }
        }

        // Ensure question belongs to assessment
        AssessmentQuestion::where('id', $validated['question_id'])
            ->where('assessment_id', $assessment_id)
            ->firstOrFail();

        // Handle answer type
        switch ($type) {
            case 'mcq':
                $answerData = $this->handleMcq($validated);
                break;

            case 'descriptive':
                $answerData = $this->handleDescriptive($validated);
                break;

            default:
                return response()->json([
                    'message' => 'Unsupported assessment type'
                ], 422);
        }

        // Save answer. updateOrCreate's own "find, then insert" isn't atomic,
        // so a duplicate/double-submitted request racing this one can still
        // hit the (attempt_id, question_id) unique constraint; treat that as
        // "someone already saved it, so update it" instead of a hard failure.
        try {
            $answer = AssessmentAnswer::updateOrCreate(
                [
                    'attempt_id' => $attempt->id,
                    'question_id' => $validated['question_id'],
                ],
                [
                    'answer' => $answerData
                ]
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $answer = AssessmentAnswer::where('attempt_id', $attempt->id)
                ->where('question_id', $validated['question_id'])
                ->firstOrFail();

            $answer->update(['answer' => $answerData]);
        }

        return response()->json([
            'message' => 'Answer saved',
            'answer' => $answer
        ]);
    }

}
