<?php

namespace App\Services\AI\Tools;

use App\Services\StudentDashboardService;
use Illuminate\Support\Facades\Auth;

class StudentDashboardTool implements AITool
{
    public function __construct(
        private StudentDashboardService $dashboardService
    ) {}

    public function actors(): array
    {
        return ['user'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'get_my_dashboard',
            'description' => 'A single snapshot of the current student\'s own exam activity: assignment status counts, result stats, available/upcoming/in-progress assessments, recent results and performance over time.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'limit' => ['type' => 'INTEGER', 'description' => 'Max rows in each embedded list. Default 5, max 50.'],
                ],
                'required' => [],
            ],
            'actions' => ['get — return the dashboard snapshot.'],
            'instructions' => [],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $user = Auth::guard('users')->user();
        $limit = min(max((int) ($arguments['limit'] ?? 5), 1), 50);

        return ['success' => true, 'data' => $this->dashboardService->get($user, $limit)];
    }
}
