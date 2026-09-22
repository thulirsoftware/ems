<?php

namespace App\Services\AI\Tools;

use App\Services\StudentAssessmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MyAssessmentTool implements AITool
{
    public function __construct(
        private StudentAssessmentService $studentAssessmentService
    ) {}

    public function actors(): array
    {
        return ['user'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'my_assessments',
            'description' => 'View the current student\'s own assigned assessments, grouped by schedule status, or a single one by id.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'action' => ['type' => 'STRING', 'enum' => ['upcoming', 'today', 'running', 'completed', 'missed', 'get']],
                    'assessment_id' => ['type' => 'INTEGER', 'description' => 'Required for get.'],
                ],
                'required' => ['action'],
            ],
            'actions' => [
                'upcoming — assigned assessments scheduled for a future date/time.',
                'today — assigned assessments scheduled later today.',
                'running — assigned assessments currently open and not yet attempted.',
                'completed — assigned assessments the student has already attempted.',
                'missed — assigned assessments whose window passed without an attempt.',
                'get — a single assigned assessment by id, with its schedule.',
            ],
            'instructions' => [
                'This tool is read-only — it cannot start or submit an attempt.',
            ],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $user = Auth::guard('users')->user();
        $action = $arguments['action'] ?? null;

        return match ($action) {
            'upcoming' => ['success' => true, 'data' => $this->studentAssessmentService->upcoming($user)],
            'today' => ['success' => true, 'data' => $this->studentAssessmentService->today($user)],
            'running' => ['success' => true, 'data' => $this->studentAssessmentService->running($user)],
            'completed' => ['success' => true, 'data' => $this->studentAssessmentService->completed($user)],
            'missed' => ['success' => true, 'data' => $this->studentAssessmentService->missed($user)],
            'get' => $this->get($user, $arguments),
            default => ['success' => false, 'message' => "Unknown action: {$action}"],
        };
    }

    private function get($user, array $arguments): array
    {
        Validator::make($arguments, ['assessment_id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->studentAssessmentService->get($user, $arguments['assessment_id'])];
    }
}
