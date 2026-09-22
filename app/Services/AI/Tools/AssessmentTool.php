<?php

namespace App\Services\AI\Tools;

use App\Models\Assessment;
use App\Services\AssessmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AssessmentTool implements AITool
{
    public function __construct(
        private AssessmentService $assessmentService
    ) {}

    public function actors(): array
    {
        return ['admin'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'manage_assessments',
            'description' => 'List, view, create, update or delete assessments owned by the current administrator.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'action' => [
                        'type' => 'STRING',
                        'enum' => ['list', 'library', 'upcoming', 'running', 'get', 'create', 'update', 'delete'],
                        'description' => 'The operation to perform.',
                    ],
                    'id' => ['type' => 'INTEGER', 'description' => 'Assessment id. Required for get, update, delete.'],
                    'assessment_type_id' => ['type' => 'INTEGER', 'description' => 'Id of the assessment type (see list_assessment_types).'],
                    'title' => ['type' => 'STRING'],
                    'description' => ['type' => 'STRING'],
                    'scheduling_type' => [
                        'type' => 'STRING',
                        'enum' => [Assessment::SCHEDULING_BATCH_WISE, Assessment::SCHEDULING_FIXED, Assessment::SCHEDULING_FLEXIBLE],
                        'description' => 'Immutable once created.',
                    ],
                    'difficulty_level' => ['type' => 'STRING'],
                    'shuffle' => ['type' => 'BOOLEAN', 'description' => 'Whether question order is shuffled per candidate.'],
                    'is_library' => ['type' => 'BOOLEAN'],
                    'is_active' => ['type' => 'BOOLEAN'],
                    'has_negative' => ['type' => 'BOOLEAN'],
                    'negative_marks' => ['type' => 'NUMBER', 'description' => 'Marks deducted per wrong answer when has_negative is true.'],
                    'publish_date' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD. Required for scheduling_type=fixed.'],
                    'start_time' => ['type' => 'STRING', 'description' => 'HH:MM:SS. Required for scheduling_type=fixed.'],
                    'end_time' => ['type' => 'STRING', 'description' => 'HH:MM:SS. Required for scheduling_type=fixed.'],
                    'start_date' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD. Required for scheduling_type=flexible.'],
                    'end_date' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD. Required for scheduling_type=flexible.'],
                    'duration_minutes' => ['type' => 'INTEGER', 'description' => 'Per-attempt time limit. Required for scheduling_type=flexible.'],
                ],
                'required' => ['action'],
            ],
            'actions' => [
                'list — every assessment owned by the current administrator, each row includes its schedule.',
                'library — assessments marked is_library=true.',
                'upcoming — active fixed/batch_wise assessments whose batch has not started yet.',
                'running — active assessments currently open for attempts.',
                'get — a single assessment by id, including its batches for batch_wise assessments.',
                'create — create a new assessment. scheduling_type is required and immutable afterwards; supply the matching schedule fields.',
                'update — update fields on an existing assessment. Blocked once any candidate has attempted it.',
                'delete — permanently delete an assessment. Blocked once any candidate has attempted it.',
            ],
            'instructions' => [
                'For batch_wise assessments, schedule fields are omitted here — batches are managed with manage_batches instead.',
                'scheduling_type cannot be changed after creation.',
            ],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $admin = Auth::guard('admins')->user();
        $action = $arguments['action'] ?? null;

        return match ($action) {
            'list' => ['success' => true, 'data' => $this->assessmentService->list($admin)],
            'library' => ['success' => true, 'data' => $this->assessmentService->library($admin)],
            'upcoming' => ['success' => true, 'data' => $this->assessmentService->upcoming($admin)],
            'running' => ['success' => true, 'data' => $this->assessmentService->running($admin)],
            'get' => $this->get($admin, $arguments),
            'create' => ['success' => true, 'message' => 'Assessment created successfully', 'data' => $this->assessmentService->create($admin, $arguments)],
            'update' => $this->update($admin, $arguments),
            'delete' => $this->delete($admin, $arguments),
            default => ['success' => false, 'message' => "Unknown action: {$action}"],
        };
    }

    private function get($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->assessmentService->get($admin, $arguments['id'])];
    }

    private function update($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        return ['success' => true, 'message' => 'Assessment updated successfully', 'data' => $this->assessmentService->update($admin, $arguments['id'], $arguments)];
    }

    private function delete($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        $this->assessmentService->delete($admin, $arguments['id']);

        return ['success' => true, 'message' => 'Assessment deleted successfully'];
    }
}
