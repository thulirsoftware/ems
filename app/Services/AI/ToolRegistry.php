<?php

namespace App\Services\AI;

use App\Services\AI\Tools\AdminAccountTool;
use App\Services\AI\Tools\AdminDashboardTool;
use App\Services\AI\Tools\AdminReportTool;
use App\Services\AI\Tools\AITool;
use App\Services\AI\Tools\AssessmentTool;
use App\Services\AI\Tools\AssessmentTypeTool;
use App\Services\AI\Tools\AssignmentTool;
use App\Services\AI\Tools\BatchTool;
use App\Services\AI\Tools\ChoiceTool;
use App\Services\AI\Tools\MyAssessmentTool;
use App\Services\AI\Tools\MyNotificationTool;
use App\Services\AI\Tools\QuestionTool;
use App\Services\AI\Tools\ReExamTool;
use App\Services\AI\Tools\ResultTool;
use App\Services\AI\Tools\StudentDashboardTool;
use App\Services\AI\Tools\StudentReportTool;
use App\Services\AI\Tools\UserAccountTool;

class ToolRegistry
{
    /**
     * @param AITool[] $tools
     */
    public function __construct(
        private AssessmentTool $assessmentTool,
        private BatchTool $batchTool,
        private QuestionTool $questionTool,
        private ChoiceTool $choiceTool,
        private AssignmentTool $assignmentTool,
        private ResultTool $resultTool,
        private AdminReportTool $adminReportTool,
        private AdminDashboardTool $adminDashboardTool,
        private UserAccountTool $userAccountTool,
        private AdminAccountTool $adminAccountTool,
        private AssessmentTypeTool $assessmentTypeTool,
        private ReExamTool $reExamTool,
        private MyAssessmentTool $myAssessmentTool,
        private StudentReportTool $studentReportTool,
        private MyNotificationTool $myNotificationTool,
        private StudentDashboardTool $studentDashboardTool
    ) {}

    /**
     * @return AITool[]
     */
    public function tools(): array
    {
        return [
            $this->assessmentTool,
            $this->batchTool,
            $this->questionTool,
            $this->choiceTool,
            $this->assignmentTool,
            $this->resultTool,
            $this->adminReportTool,
            $this->adminDashboardTool,
            $this->userAccountTool,
            $this->adminAccountTool,
            $this->assessmentTypeTool,
            $this->reExamTool,
            $this->myAssessmentTool,
            $this->studentReportTool,
            $this->myNotificationTool,
            $this->studentDashboardTool,
        ];
    }

    // $actor is 'admin' or 'user'
    public function toolsForActor(string $actor): array
    {
        return collect($this->tools())
            ->filter(fn ($tool) => in_array($actor, $tool->actors(), true))
            ->values()
            ->all();
    }

    public function find(
        string $name,
        string $actor
    ): ?AITool {
        foreach ($this->toolsForActor($actor) as $tool) {
            if ($tool->metadata()['name'] === $name) {
                return $tool;
            }
        }

        return null;
    }

    public function geminiTools(string $actor): array
    {
        return collect($this->toolsForActor($actor))
            ->map(function ($tool) {
                $meta = $tool->metadata();

                return [
                    'type' => 'function',
                    'name' => $meta['name'],
                    'description' => $meta['description'],
                    'parameters' => $meta['parameters'],
                ];
            })
            ->values()
            ->all();
    }

    public function prompt(string $actor): string
    {
        return collect($this->toolsForActor($actor))
            ->map(function ($tool) {
                $meta = $tool->metadata();

                return $meta['name'].
                    "\n\nSupported actions:\n- ".
                    implode(
                        "\n- ",
                        $meta['actions']
                    ).
                    "\n\n".
                    implode(
                        "\n",
                        $meta['instructions']
                    );
            })
            ->implode("\n\n");
    }
}
