<?php

namespace App\Services\AI\Tools;

use App\Services\ResultService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ResultTool implements AITool
{
    public function __construct(
        private ResultService $resultService
    ) {}

    public function actors(): array
    {
        return ['admin'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'manage_results',
            'description' => 'View finished assessments, candidate attempt/grading status, grade descriptive answers, view a candidate\'s evaluated result, and view an assessment\'s rank list.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'action' => [
                        'type' => 'STRING',
                        'enum' => ['finished_assessments', 'users_by_assessment', 'answers_for_grading', 'grade_answer', 'user_result', 'rank_list'],
                    ],
                    'assessment_id' => ['type' => 'INTEGER'],
                    'user_id' => ['type' => 'INTEGER', 'description' => 'Required for answers_for_grading, grade_answer, user_result.'],
                    'question_id' => ['type' => 'INTEGER', 'description' => 'Required for grade_answer.'],
                    'is_correct' => ['type' => 'BOOLEAN', 'description' => 'Required for grade_answer.'],
                    'batch_id' => ['type' => 'INTEGER', 'description' => 'Required for batch_wise assessments; omit for fixed/flexible.'],
                ],
                'required' => ['action'],
            ],
            'actions' => [
                'finished_assessments — active assessments whose batch has ended (or, for flexible assessments, are always eligible).',
                'users_by_assessment — assigned candidates with whether they have attempted and whether grading is still pending.',
                'answers_for_grading — a submitted, not-yet-fully-evaluated candidate\'s answers, ready for grade_answer.',
                'grade_answer — mark one descriptive answer correct or incorrect. Once every question is graded the attempt\'s final score is calculated automatically.',
                'user_result — a candidate\'s fully evaluated result with per-question breakdown.',
                'rank_list — every submitted, evaluated attempt for an assessment, ranked by percentage.',
            ],
            'instructions' => [
                'MCQ assessments never need grade_answer — they are scored automatically at submission.',
                'grade_answer must only be called after explicit administrator confirmation, since it can finalize the candidate\'s score.',
            ],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $admin = Auth::guard('admins')->user();
        $action = $arguments['action'] ?? null;

        return match ($action) {
            'finished_assessments' => ['success' => true, 'data' => $this->resultService->finishedAssessments($admin)],
            'users_by_assessment' => $this->usersByAssessment($admin, $arguments),
            'answers_for_grading' => $this->answersForGrading($admin, $arguments),
            'grade_answer' => $this->gradeAnswer($admin, $arguments),
            'user_result' => $this->userResult($admin, $arguments),
            'rank_list' => $this->rankList($admin, $arguments),
            default => ['success' => false, 'message' => "Unknown action: {$action}"],
        };
    }

    private function usersByAssessment($admin, array $arguments): array
    {
        Validator::make($arguments, ['assessment_id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->resultService->usersByAssessment($admin, $arguments['assessment_id'], $arguments['batch_id'] ?? null)];
    }

    private function answersForGrading($admin, array $arguments): array
    {
        Validator::make($arguments, ['assessment_id' => 'required|integer', 'user_id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->resultService->answersForGrading($admin, $arguments['assessment_id'], $arguments['user_id'], $arguments['batch_id'] ?? null)];
    }

    private function gradeAnswer($admin, array $arguments): array
    {
        $result = $this->resultService->gradeAnswer($admin, $arguments);

        if ($result['final']) {
            return ['success' => true, 'message' => 'Answer graded. Final score calculated.', 'score' => $result['score']];
        }

        return ['success' => true, 'message' => 'Answer graded successfully', 'graded' => $result['graded'], 'total' => $result['total']];
    }

    private function userResult($admin, array $arguments): array
    {
        Validator::make($arguments, ['assessment_id' => 'required|integer', 'user_id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->resultService->userResult($admin, $arguments['assessment_id'], $arguments['user_id'], $arguments['batch_id'] ?? null)];
    }

    private function rankList($admin, array $arguments): array
    {
        Validator::make($arguments, ['assessment_id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->resultService->rankList($admin, $arguments['assessment_id'], $arguments['batch_id'] ?? null)];
    }
}
