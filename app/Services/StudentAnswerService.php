<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentChoice;
use App\Models\AssessmentQuestion;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class StudentAnswerService
{
    private function handleMcq(array $data): array
    {
        AssessmentChoice::where('id', $data['choice_id'])
            ->where('question_id', $data['question_id'])
            ->firstOrFail();

        return ['choice_id' => $data['choice_id']];
    }

    private function handleDescriptive(array $data): array
    {
        // not empty(): "0" is a valid answer
        if (!isset($data['answer']) || trim($data['answer']) === '') {
            abort(422, 'Answer is required');
        }

        return ['text' => $data['answer']];
    }

    public function store(User $user, $assessmentId, array $data): AssessmentAnswer
    {
        $assessment = Assessment::with('type')->where('id', $assessmentId)->firstOrFail();

        $type = $assessment->type->slug;

        $rules = [
            'question_id' => 'required|exists:assessment_questions,id',
        ];

        $rules['choice_id'] = $type === 'mcq'
            ? 'required|exists:assessment_choices,id'
            : 'nullable|exists:assessment_choices,id';

        $rules['answer'] = 'nullable|string';

        $validated = Validator::make($data, $rules)->validate();

        $assignment = AssessmentAssignment::where('assessment_id', $assessmentId)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $attempt = AssessmentAttempt::where('assessment_id', $assessmentId)
            ->where('user_id', $user->id)
            ->where('batch_id', $assignment->batch_id)
            ->firstOrFail();

        if ($attempt->submitted_at) {
            abort(403, 'Assessment already submitted');
        }

        $batch = Batch::findOrFail($assignment->batch_id);

        // Answers are never accepted after the attempt's deadline — the
        // attempt itself stays open for submission (see StudentAttemptService).
        if (!attempt_answering_open($assessment, $batch, $attempt)) {
            abort(403, 'Assessment time expired');
        }

        AssessmentQuestion::where('id', $validated['question_id'])
            ->where('assessment_id', $assessmentId)
            ->firstOrFail();

        $answerData = match ($type) {
            'mcq' => $this->handleMcq($validated),
            'descriptive' => $this->handleDescriptive($validated),
            default => null,
        };

        if ($answerData === null) {
            abort(422, 'Unsupported assessment type');
        }

        try {
            $answer = AssessmentAnswer::updateOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $validated['question_id']],
                ['answer' => $answerData]
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $answer = AssessmentAnswer::where('attempt_id', $attempt->id)
                ->where('question_id', $validated['question_id'])
                ->firstOrFail();

            $answer->update(['answer' => $answerData]);
        }

        return $answer;
    }
}
