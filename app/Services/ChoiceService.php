<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentChoice;
use App\Models\AssessmentQuestion;
use Illuminate\Support\Facades\Validator;

class ChoiceService
{
    private function isLocked(int $assessmentId): bool
    {
        return AssessmentAttempt::where('assessment_id', $assessmentId)->exists();
    }

    // $id is untyped — see the comment on AssessmentService::findOwned() for why.
    private function ownedQuestion(Admin $admin, $id): AssessmentQuestion
    {
        return AssessmentQuestion::whereHas('assessment', fn ($q) => $q->where('admin_id', $admin->id))
            ->where('id', $id)
            ->firstOrFail();
    }

    private function ownedChoice(Admin $admin, $id): AssessmentChoice
    {
        return AssessmentChoice::whereHas('question.assessment', fn ($q) => $q->where('admin_id', $admin->id))
            ->where('id', $id)
            ->firstOrFail();
    }

    public function list(Admin $admin, $questionId)
    {
        $this->ownedQuestion($admin, $questionId);

        return AssessmentChoice::where('question_id', $questionId)->orderBy('order')->get();
    }

    public function create(Admin $admin, $questionId, array $data): AssessmentChoice
    {
        $question = $this->ownedQuestion($admin, $questionId);

        if ($this->isLocked($question->assessment_id)) {
            abort(403, 'Cannot modify choices after assessment has been attempted');
        }

        $validated = Validator::make($data, [
            'option' => 'required|string',
            'is_correct' => 'boolean',
            'order' => 'nullable|integer',
        ])->validate();

        return AssessmentChoice::create(['question_id' => $question->id, ...$validated]);
    }

    public function update(Admin $admin, $id, array $data): AssessmentChoice
    {
        $choice = $this->ownedChoice($admin, $id);

        if ($this->isLocked($choice->question->assessment_id)) {
            abort(403, 'Cannot modify choices after assessment has been attempted');
        }

        $validated = Validator::make($data, [
            'option' => 'sometimes|string',
            'is_correct' => 'boolean',
            'order' => 'nullable|integer',
        ])->validate();

        $choice->update($validated);

        return $choice;
    }

    public function delete(Admin $admin, $id): void
    {
        $choice = $this->ownedChoice($admin, $id);

        if ($this->isLocked($choice->question->assessment_id)) {
            abort(403, 'Cannot delete choices after assessment has been attempted');
        }

        $choice->delete();
    }
}
