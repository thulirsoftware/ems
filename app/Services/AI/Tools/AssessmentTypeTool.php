<?php

namespace App\Services\AI\Tools;

use App\Services\AssessmentTypeService;

class AssessmentTypeTool implements AITool
{
    public function __construct(
        private AssessmentTypeService $assessmentTypeService
    ) {}

    public function actors(): array
    {
        return ['admin'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'list_assessment_types',
            'description' => 'List every assessment type available to assign to a new assessment.',
            'parameters' => ['type' => 'OBJECT', 'properties' => [], 'required' => []],
            'actions' => ['list — every assessment type.'],
            'instructions' => [],
        ];
    }

    public function execute(array $arguments): mixed
    {
        return ['success' => true, 'data' => $this->assessmentTypeService->list()];
    }
}
