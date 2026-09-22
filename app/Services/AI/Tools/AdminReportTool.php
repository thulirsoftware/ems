<?php

namespace App\Services\AI\Tools;

use App\Services\AdminReportService;
use Illuminate\Support\Facades\Auth;

class AdminReportTool implements AITool
{
    public function __construct(
        private AdminReportService $reportService
    ) {}

    public function actors(): array
    {
        return ['admin'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'view_reports',
            'description' => 'Analytics/reporting across every assessment owned by the current administrator, filterable by assessment, batch, candidate, type, status, passing percentage and date range.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'report' => ['type' => 'STRING', 'enum' => ['summary', 'assessments', 'batches', 'users', 'questions', 'attempts']],
                    'assessment_id' => ['type' => 'INTEGER'],
                    'batch_id' => ['type' => 'INTEGER'],
                    'user_id' => ['type' => 'INTEGER'],
                    'assessment_type' => ['type' => 'STRING', 'description' => 'Assessment type slug, e.g. mcq or descriptive.'],
                    'status' => ['type' => 'STRING', 'enum' => ['in_progress', 'pending_evaluation', 'evaluated']],
                    'passing_percentage' => ['type' => 'NUMBER', 'description' => 'Defaults to 40 when omitted.'],
                    'from' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD'],
                    'to' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD'],
                    'page' => ['type' => 'INTEGER'],
                    'page_size' => ['type' => 'INTEGER', 'description' => 'Max 200, default 25.'],
                ],
                'required' => ['report'],
            ],
            'actions' => [
                'summary — headline totals, attempt counts, participation and score distribution across everything matching the filters.',
                'assessments — one row per assessment: counts and performance.',
                'batches — one row per batch_wise batch: schedule status, participation and performance.',
                'users — one row per candidate: assigned/attempted counts and performance.',
                'questions — one row per question: how often it was answered, its accuracy and inferred difficulty.',
                'attempts — a detailed, filterable attempt log with score and pass/fail per row.',
            ],
            'instructions' => [
                'assessments, batches, users, questions and attempts are paginated — read total_pages and continue with an incremented page until every page needed is retrieved.',
            ],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $admin = Auth::guard('admins')->user();
        $report = $arguments['report'] ?? null;

        return match ($report) {
            'summary' => ['success' => true, 'data' => $this->reportService->summary($admin, $arguments)],
            'assessments' => ['success' => true, 'data' => $this->reportService->assessments($admin, $arguments)],
            'batches' => ['success' => true, 'data' => $this->reportService->batches($admin, $arguments)],
            'users' => ['success' => true, 'data' => $this->reportService->users($admin, $arguments)],
            'questions' => ['success' => true, 'data' => $this->reportService->questions($admin, $arguments)],
            'attempts' => ['success' => true, 'data' => $this->reportService->attempts($admin, $arguments)],
            default => ['success' => false, 'message' => "Unknown report: {$report}"],
        };
    }
}
