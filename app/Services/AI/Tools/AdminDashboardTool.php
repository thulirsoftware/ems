<?php

namespace App\Services\AI\Tools;

use App\Services\AdminDashboardService;
use Illuminate\Support\Facades\Auth;

class AdminDashboardTool implements AITool
{
    public function __construct(
        private AdminDashboardService $dashboardService
    ) {}

    public function actors(): array
    {
        return ['admin'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'get_admin_dashboard',
            'description' => 'A single snapshot of the current administrator\'s exam activity: assessment/batch/attempt counters, top performers, upcoming batches, recent attempts and pending evaluations.',
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
        $admin = Auth::guard('admins')->user();
        $limit = min(max((int) ($arguments['limit'] ?? 5), 1), 50);

        return ['success' => true, 'data' => $this->dashboardService->get($admin, $limit)];
    }
}
