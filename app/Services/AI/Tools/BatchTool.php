<?php

namespace App\Services\AI\Tools;

use App\Services\BatchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BatchTool implements AITool
{
    public function __construct(
        private BatchService $batchService
    ) {}

    public function actors(): array
    {
        return ['admin'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'manage_batches',
            'description' => 'List, create, update, delete batches for batch_wise assessments, and manage which candidates are assigned to a batch.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'action' => [
                        'type' => 'STRING',
                        'enum' => ['list', 'list_by_assessment', 'get', 'create', 'update', 'delete', 'list_users', 'add_users', 'remove_users'],
                    ],
                    'id' => ['type' => 'INTEGER', 'description' => 'Batch id. Required for get, update, delete, list_users, add_users, remove_users.'],
                    'assessment_id' => ['type' => 'INTEGER', 'description' => 'Required for create and list_by_assessment. Must be a batch_wise assessment.'],
                    'name' => ['type' => 'STRING'],
                    'publish_date' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD'],
                    'start_time' => ['type' => 'STRING', 'description' => 'HH:MM:SS'],
                    'end_time' => ['type' => 'STRING', 'description' => 'HH:MM:SS'],
                    'capacity' => ['type' => 'INTEGER'],
                    'user_ids' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER'], 'description' => 'Required for add_users, remove_users.'],
                ],
                'required' => ['action'],
            ],
            'actions' => [
                'list — every batch across the administrator\'s batch_wise assessments.',
                'list_by_assessment — batches for one batch_wise assessment.',
                'get — a single batch by id.',
                'create — create a batch for a batch_wise assessment. Fails on a publish_date/time overlap with another batch of the same assessment.',
                'update — update a batch\'s name, schedule or capacity. Blocked once any candidate has attempted it.',
                'delete — permanently delete a batch. Blocked once any candidate has attempted it.',
                'list_users — candidates currently assigned to a batch.',
                'add_users — assign candidates to a batch, respecting its capacity. Blocked once any candidate has attempted it.',
                'remove_users — unassign candidates from a batch. Blocked once any candidate has attempted it.',
            ],
            'instructions' => [
                'Batches only apply to batch_wise assessments — fixed and flexible assessments manage their own hidden schedule through manage_assessments instead.',
            ],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $admin = Auth::guard('admins')->user();
        $action = $arguments['action'] ?? null;

        return match ($action) {
            'list' => ['success' => true, 'data' => $this->batchService->list($admin)],
            'list_by_assessment' => $this->listByAssessment($admin, $arguments),
            'get' => $this->get($admin, $arguments),
            'create' => ['success' => true, 'message' => 'Batch created successfully', 'data' => $this->batchService->create($admin, $arguments)],
            'update' => $this->update($admin, $arguments),
            'delete' => $this->delete($admin, $arguments),
            'list_users' => $this->listUsers($admin, $arguments),
            'add_users' => $this->addUsers($admin, $arguments),
            'remove_users' => $this->removeUsers($admin, $arguments),
            default => ['success' => false, 'message' => "Unknown action: {$action}"],
        };
    }

    private function listByAssessment($admin, array $arguments): array
    {
        Validator::make($arguments, ['assessment_id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->batchService->listByAssessment($admin, $arguments['assessment_id'])];
    }

    private function get($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->batchService->get($admin, $arguments['id'])];
    }

    private function update($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        return ['success' => true, 'message' => 'Batch updated successfully', 'data' => $this->batchService->update($admin, $arguments['id'], $arguments)];
    }

    private function delete($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        $this->batchService->delete($admin, $arguments['id']);

        return ['success' => true, 'message' => 'Batch deleted'];
    }

    private function listUsers($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->batchService->listUsers($admin, $arguments['id'])];
    }

    private function addUsers($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        $result = $this->batchService->addUsers($admin, $arguments['id'], $arguments);

        return ['success' => true, 'message' => 'Users assigned successfully', ...$result];
    }

    private function removeUsers($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        $deleted = $this->batchService->removeUsers($admin, $arguments['id'], $arguments);

        return ['success' => true, 'message' => 'Users unassigned successfully', 'deleted_count' => $deleted];
    }
}
