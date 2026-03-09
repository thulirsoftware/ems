<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentChoice;
use Illuminate\Http\Request;

class UserAnswerController extends Controller
{
    public function store(Request $request, $assessment_id)
    {
        $user = $request->user('users');

        $validated = $request->validate([
            'question_id' => 'required|exists:assessment_questions,id',
            'choice_id' => 'required|exists:assessment_choices,id',
            'answer' => 'nullable|string'
        ]);

        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($attempt->submitted_at) {
            return response()->json([
                'message' => 'Assessment already submitted'
            ], 403);
        }

        $assessment = Assessment::with('type')
            ->where('id', $assessment_id)
            ->firstOrFail();

        $type = $assessment->type->slug;

        switch ($type) {
            case 'mcq':
                $answerData = $this->handleMcq($validated);
                break;

            case 'descriptive':
                $answerData = $this->handleDescriptive($validated);
                break;

            case 'coding':
                $answerData = $this->handleCoding($validated);
                break;

            case 'case_based':
                $answerData = $this->handleCaseBased($validated);
                break;

            default:
                return response()->json([
                    'message' => 'Unsupported assessment type'
                ], 422);
        }

        $answer = AssessmentAnswer::updateOrCreate(
            [
                'attempt_id' => $attempt->id,
                'question_id' => $validated['question_id'],
            ],
            [
                'answer' => $answerData
            ]
        );

        return response()->json([
            'message' => 'Answer saved',
            'answer' => $answer
        ]);
    }

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

    private function handleCoding(array $data)
    {
        if (empty($data['answer'])) {
            abort(422, 'Code answer is required');
        }

        return [
            'code' => $data['answer']
        ];
    }

    private function handleCaseBased(array $data)
    {
        if (empty($data['answer'])) {
            abort(422, 'Answer is required');
        }

        return [
            'text' => $data['answer']
        ];
    }
}
