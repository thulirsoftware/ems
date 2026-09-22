<?php

namespace App\Services\AI\Tools;

use App\Services\AssignmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AssignmentTool implements AITool
{
    public function __construct(
        private AssignmentService $assignmentService
    ) {}

    public function actors(): array
    {
        return ['admin'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'manage_assignments',
            'description' => 'View candidate assignment status for an assessment, and assign or unassign candidates.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'action' => ['type' => 'STRING', 'enum' => ['status', 'assign', 'unassign']],
                    'assessment_id' => ['type' => 'INTEGER'],
                    'batch_id' => [
                        'type' => 'INTEGER',
                        'description' => 'Required for batch_wise assessments; omit for fixed/flexible assessments (their single hidden batch is used automatically).',
                    ],
                    'user_ids' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER'], 'description' => 'Required for assign, unassign.'],
                ],
                'required' => ['action', 'assessment_id'],
            ],
            'actions' => [
                'status — every candidate with whether they are assigned to the given assessment/batch and whether they have a conflicting assignment at the same time elsewhere.',
                'assign — assign candidates to the assessment/batch. Candidates with a time conflict against another assessment are skipped and reported back, not assigned.',
                'unassign — remove candidates from the assessment/batch.',
            ],
            'instructions' => [
                'assign/unassign are blocked once any candidate has attempted the target batch.',
                'For batch_wise assessments batch_id must be resolved first (see manage_batches list_by_assessment).',
            ],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $admin = Auth::guard('admins')->user();
        $action = $arguments['action'] ?? null;

        return match ($action) {
            'status' => $this->status($admin, $arguments),
            'assign' => $this->assign($admin, $arguments),
            'unassign' => $this->unassign($admin, $arguments),
            default => ['success' => false, 'message' => "Unknown action: {$action}"],
        };
    }

    private function status($admin, array $arguments): array
    {
        Validator::make($arguments, ['assessment_id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->assignmentService->status($admin, $arguments['assessment_id'], $arguments['batch_id'] ?? null)];
    }

    private function assign($admin, array $arguments): array
    {
        $result = $this->assignmentService->assign($admin, $arguments);

        return ['success' => true, 'message' => 'Assignment completed', ...$result];
    }

    private function unassign($admin, array $arguments): array
    {
        $deleted = $this->assignmentService->unassign($admin, $arguments);

        return ['success' => true, 'message' => 'Users unassigned successfully', 'deleted_count' => $deleted];
    }
}
