<?php

namespace App\Services\AI\Tools;

use App\Services\StudentReportService;
use Illuminate\Support\Facades\Auth;

class StudentReportTool implements AITool
{
    public function __construct(
        private StudentReportService $studentReportService
    ) {}

    public function actors(): array
    {
        return ['user'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'my_reports',
            'description' => 'The current student\'s own performance reports: summary, per-assessment breakdown, attempt history, per-question breakdown, and progress over time.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'report' => ['type' => 'STRING', 'enum' => ['summary', 'assessments', 'attempts', 'questions', 'progress']],
                    'assessment_id' => ['type' => 'INTEGER'],
                    'batch_id' => ['type' => 'INTEGER'],
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
                'summary — headline totals, attempt counts, participation and score distribution.',
                'assessments — one row per assessment attempted, with performance.',
                'attempts — a detailed attempt history with score and pass/fail per row.',
                'questions — a question-level breakdown of the student\'s own submitted answers.',
                'progress — evaluated attempts over time with a running average and overall trend.',
            ],
            'instructions' => [
                'assessments, attempts, questions and progress are paginated — read total_pages and continue with an incremented page until every page needed is retrieved.',
            ],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $user = Auth::guard('users')->user();
        $report = $arguments['report'] ?? null;

        return match ($report) {
            'summary' => ['success' => true, 'data' => $this->studentReportService->summary($user, $arguments)],
            'assessments' => ['success' => true, 'data' => $this->studentReportService->assessments($user, $arguments)],
            'attempts' => ['success' => true, 'data' => $this->studentReportService->attempts($user, $arguments)],
            'questions' => ['success' => true, 'data' => $this->studentReportService->questions($user, $arguments)],
            'progress' => ['success' => true, 'data' => $this->studentReportService->progress($user, $arguments)],
            default => ['success' => false, 'message' => "Unknown report: {$report}"],
        };
    }
}
