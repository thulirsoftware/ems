<?php

namespace App\Services\AI\Tools;

use App\Services\ReExamService;
use Illuminate\Support\Facades\Auth;

class ReExamTool implements AITool
{
    public function __construct(
        private ReExamService $reExamService
    ) {}

    public function actors(): array
    {
        return ['admin'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'manage_re_exams',
            'description' => 'Schedule a re-exam batch for a fixed or batch_wise assessment, either for an explicit list of candidates or automatically filtered by failed/absent status.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'action' => ['type' => 'STRING', 'enum' => ['create', 'create_filtered']],
                    'assessment_id' => ['type' => 'INTEGER'],
                    'source_batch_id' => ['type' => 'INTEGER', 'description' => 'Required for batch_wise assessments.'],
                    'use_previous_users' => ['type' => 'BOOLEAN', 'description' => 'create only: reuse everyone assigned to source_batch_id instead of an explicit list.'],
                    'user_ids' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER'], 'description' => 'create only, required when use_previous_users is false.'],
                    'filter' => ['type' => 'STRING', 'enum' => ['failed', 'absent', 'both'], 'description' => 'create_filtered only.'],
                    'passing_percentage' => ['type' => 'NUMBER', 'description' => 'create_filtered only, required when filter is failed or both.'],
                    'publish_date' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD'],
                    'start_time' => ['type' => 'STRING', 'description' => 'HH:MM:SS'],
                    'end_time' => ['type' => 'STRING', 'description' => 'HH:MM:SS'],
                ],
                'required' => ['action', 'assessment_id', 'publish_date', 'start_time', 'end_time'],
            ],
            'actions' => [
                'create — schedule a re-exam batch for an explicit candidate list, or for everyone in source_batch_id when use_previous_users is true.',
                'create_filtered — schedule a re-exam batch automatically for candidates who failed the source batch, were absent, or both.',
            ],
            'instructions' => [
                'Flexible assessments do not support re-exams.',
                'The source batch must have already finished before a re-exam can be scheduled against it.',
                'Always confirm the candidate list/filter and the new schedule with the administrator before calling this tool.',
            ],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $admin = Auth::guard('admins')->user();
        $action = $arguments['action'] ?? null;

        return match ($action) {
            'create' => ['success' => true, 'message' => 'Re-exam created successfully', ...$this->reExamService->create($admin, $arguments)],
            'create_filtered' => ['success' => true, 'message' => 'Filtered re-exam created successfully', ...$this->reExamService->createFiltered($admin, $arguments)],
            default => ['success' => false, 'message' => "Unknown action: {$action}"],
        };
    }
}
