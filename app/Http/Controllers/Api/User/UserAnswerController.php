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

        // 🔥 Get latest assignment (re-exam safe)
        $assignment = \App\Models\AssessmentAssignment::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        // 🔥 Get correct attempt (batch-aware)
        $attempt = AssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', $user->id)
            ->where('batch_id', $assignment->batch_id)
            ->firstOrFail();

        if ($attempt->submitted_at) {
            return response()->json([
                'message' => 'Assessment already submitted'
            ], 403);
        }

        $batch = \App\Models\Batch::findOrFail(
            $assignment->batch_id
        );

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

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

                if (app_now()->gt($expiresAt)) {
                    return response()->json([
                        'message' => 'Assessment time expired'
                    ], 403);
                }
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

        // 🔥 Ensure question belongs to assessment
        \App\Models\AssessmentQuestion::where('id', $validated['question_id'])
            ->where('assessment_id', $assessment_id)
            ->firstOrFail();

        // 🔥 Handle answer type
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

        // 🔥 Save answer
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
