<?php

namespace App\Services\AI\Tools;

use App\Services\QuestionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class QuestionTool implements AITool
{
    public function __construct(
        private QuestionService $questionService
    ) {}

    public function actors(): array
    {
        return ['admin'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'manage_questions',
            'description' => 'List, view, create, update or delete questions belonging to an assessment owned by the current administrator. MCQ questions can be created/updated together with their choices in one call.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'action' => [
                        'type' => 'STRING',
                        'enum' => ['list', 'list_with_choices', 'get_with_choices', 'create', 'update', 'delete', 'create_with_choices', 'update_with_choices', 'bulk_create'],
                    ],
                    'assessment_id' => ['type' => 'INTEGER', 'description' => 'Required for list, list_with_choices, create, create_with_choices, bulk_create.'],
                    'id' => ['type' => 'INTEGER', 'description' => 'Question id. Required for get_with_choices, update, delete, update_with_choices.'],
                    'type' => ['type' => 'STRING', 'enum' => ['mcq', 'descriptive'], 'description' => 'create_with_choices/update_with_choices only accept mcq.'],
                    'question_text' => ['type' => 'STRING'],
                    'order' => ['type' => 'INTEGER'],
                    'config' => ['type' => 'OBJECT', 'description' => 'Optional free-form per-question settings, only used by create/update (not create_with_choices/update_with_choices).', 'properties' => []],
                    'choices' => [
                        'type' => 'ARRAY',
                        'description' => 'For create_with_choices/update_with_choices: exactly 4 choices, exactly one with is_correct=true.',
                        'items' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'option' => ['type' => 'STRING'],
                                'is_correct' => ['type' => 'BOOLEAN'],
                                'order' => ['type' => 'INTEGER'],
                            ],
                        ],
                    ],
                    'questions' => [
                        'type' => 'ARRAY',
                        'description' => 'For bulk_create: one entry per question. Omit choice_1..choice_4/correct_choice for a descriptive question; include all of them for an MCQ question.',
                        'items' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'question_text' => ['type' => 'STRING'],
                                'choice_1' => ['type' => 'STRING'],
                                'choice_2' => ['type' => 'STRING'],
                                'choice_3' => ['type' => 'STRING'],
                                'choice_4' => ['type' => 'STRING'],
                                'correct_choice' => ['type' => 'STRING', 'description' => 'Must exactly match the text of one of the supplied choices.'],
                            ],
                        ],
                    ],
                ],
                'required' => ['action'],
            ],
            'actions' => [
                'list — plain questions for an assessment, ordered.',
                'list_with_choices — questions with their MCQ choices included.',
                'get_with_choices — a single question with its choices.',
                'create — create a bare question (used for descriptive questions, or MCQ questions whose choices are added separately via manage_choices).',
                'update — update a bare question\'s text/order/type.',
                'delete — permanently delete a question and its choices. Blocked once the assessment has been attempted.',
                'create_with_choices — create an MCQ question together with exactly 4 choices in one call.',
                'update_with_choices — replace an MCQ question\'s text and its full set of 4 choices in one call.',
                'bulk_create — create many questions at once (descriptive and/or MCQ, mixed freely), appended after the assessment\'s existing questions in order.',
            ],
            'instructions' => [
                'All question mutations are blocked once any candidate has attempted the parent assessment.',
                'For bulk_create, report back per-row failures (with the reason) instead of silently dropping them.',
            ],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $admin = Auth::guard('admins')->user();
        $action = $arguments['action'] ?? null;

        return match ($action) {
            'list' => $this->list($admin, $arguments),
            'list_with_choices' => $this->listWithChoices($admin, $arguments),
            'get_with_choices' => $this->getWithChoices($admin, $arguments),
            'create' => $this->create($admin, $arguments),
            'update' => $this->update($admin, $arguments),
            'delete' => $this->delete($admin, $arguments),
            'create_with_choices' => $this->createWithChoices($admin, $arguments),
            'update_with_choices' => $this->updateWithChoices($admin, $arguments),
            'bulk_create' => $this->bulkCreate($admin, $arguments),
            default => ['success' => false, 'message' => "Unknown action: {$action}"],
        };
    }

    private function list($admin, array $arguments): array
    {
        Validator::make($arguments, ['assessment_id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->questionService->list($admin, $arguments['assessment_id'])];
    }

    private function listWithChoices($admin, array $arguments): array
    {
        Validator::make($arguments, ['assessment_id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->questionService->listWithChoices($admin, $arguments['assessment_id'])];
    }

    private function getWithChoices($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        return ['success' => true, 'data' => $this->questionService->getWithChoices($admin, $arguments['id'])];
    }

    private function create($admin, array $arguments): array
    {
        Validator::make($arguments, ['assessment_id' => 'required|integer'])->validate();

        return ['success' => true, 'message' => 'Question created', 'data' => $this->questionService->create($admin, $arguments['assessment_id'], $arguments)];
    }

    private function update($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        return ['success' => true, 'message' => 'Question updated', 'data' => $this->questionService->update($admin, $arguments['id'], $arguments)];
    }

    private function delete($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        $this->questionService->delete($admin, $arguments['id']);

        return ['success' => true, 'message' => 'Question deleted'];
    }

    private function createWithChoices($admin, array $arguments): array
    {
        Validator::make($arguments, ['assessment_id' => 'required|integer'])->validate();

        return ['success' => true, 'message' => 'Question created', 'data' => $this->questionService->createWithChoices($admin, $arguments['assessment_id'], $arguments)];
    }

    private function updateWithChoices($admin, array $arguments): array
    {
        Validator::make($arguments, ['id' => 'required|integer'])->validate();

        return ['success' => true, 'message' => 'Question updated', 'data' => $this->questionService->updateWithChoices($admin, $arguments['id'], $arguments)];
    }

    private function bulkCreate($admin, array $arguments): array
    {
        $validated = Validator::make($arguments, [
            'assessment_id' => 'required|integer',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
        ])->validate();

        $result = $this->questionService->bulkCreate($admin, $validated['assessment_id'], $validated['questions']);

        return ['success' => true, 'message' => 'Bulk questions upload completed', ...$result];
    }
}
