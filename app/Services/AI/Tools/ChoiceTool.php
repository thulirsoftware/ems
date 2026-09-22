<?php

namespace App\Services\AI\Tools;

use App\Services\ChoiceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChoiceTool implements AITool
{
    public function __construct(
        private ChoiceService $choiceService
    ) {}

    public function actors(): array
    {
        return ['admin'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'manage_choices',
            'description' => 'List, create, update or delete individual MCQ choices for a question. Prefer manage_questions\' create_with_choices/update_with_choices for whole-question edits.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'action' => ['type' => 'STRING', 'enum' => ['list', 'create', 'update', 'delete']],
                    'question_id' => ['type' => 'INTEGER', 'description' => 'Required for list, create.'],
                    'id' => ['type' => 'INTEGER', 'description' => 'Choice id. Required for update, delete.'],
                    'option' => ['type' => 'STRING'],
                    'is_correct' => ['type' => 'BOOLEAN'],
                    'order' => ['type' => 'INTEGER'],
                ],
                'required' => ['action'],
            ],
            'actions' => [
                'list — choices for a question, ordered.',
                'create — add one choice to a question.',
                'update — update one choice.',
                'delete — permanently delete one choice.',
            ],
            'instructions' => [
                'All mutations are blocked once any candidate has attempted the parent assessment.',
            ],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $admin = Auth::guard('admins')->user();
        $action = $arguments['action'] ?? null;

        return match ($action) {
            'list' => $this->list($admin, $arguments),
            'create' => $this->create($admin, $arguments),
            'update' => $this->update($admin, $arguments),
            'delete' => $this->delete($admin, $arguments),
            default => ['success' => false, 'message' => "Unknown action: {$action}"],
        };
    }

    private function list($admin, array $arguments): array
    {
        Validator::make($arguments, ['question_id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->choiceService->list($admin, $arguments['question_id'])];
    }

    private function create($admin, array $arguments): array
    {
        Validator::make($arguments, ['question_id' => 'required|integer'])->validate();

        return ['success' => true, 'message' => 'Choice created', 'data' => $this->choiceService->create($admin, $arguments['question_id'], $arguments)];
    }

    private function update($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        return ['success' => true, 'message' => 'Choice updated', 'data' => $this->choiceService->update($admin, $arguments['id'], $arguments)];
    }

    private function delete($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        $this->choiceService->delete($admin, $arguments['id']);

        return ['success' => true, 'message' => 'Choice deleted'];
    }
}
