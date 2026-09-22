<?php

namespace App\Services\AI\Tools;

use App\Services\UserAccountService;
use Illuminate\Support\Facades\Validator;

class UserAccountTool implements AITool
{
    public function __construct(
        private UserAccountService $userAccountService
    ) {}

    public function actors(): array
    {
        return ['admin'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'manage_user_accounts',
            'description' => 'List every candidate account, create one, or create many at once.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'action' => ['type' => 'STRING', 'enum' => ['list', 'create', 'bulk_create']],
                    'name' => ['type' => 'STRING'],
                    'email' => ['type' => 'STRING'],
                    'password' => ['type' => 'STRING', 'description' => 'Minimum 8 characters.'],
                    'users' => [
                        'type' => 'ARRAY',
                        'description' => 'For bulk_create: one entry per candidate. Omit password to auto-generate a temporary one, which is returned so it can be shared with the candidate.',
                        'items' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'name' => ['type' => 'STRING'],
                                'email' => ['type' => 'STRING'],
                                'password' => ['type' => 'STRING'],
                            ],
                        ],
                    ],
                ],
                'required' => ['action'],
            ],
            'actions' => [
                'list — every candidate account in the system (not scoped to one administrator).',
                'create — create one candidate account.',
                'bulk_create — create many candidate accounts at once, skipping rows with an invalid or already-used email.',
            ],
            'instructions' => [
                'Never reveal a candidate\'s own-chosen password back to the administrator; only confirm the account was created.',
                'bulk_create is the only case where a password is ever shown back — only the auto-generated temporary ones, since the administrator has no other way to learn them.',
            ],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $action = $arguments['action'] ?? null;

        return match ($action) {
            'list' => ['success' => true, 'data' => $this->userAccountService->list()],
            'create' => ['success' => true, 'message' => 'Candidate account created', 'data' => $this->userAccountService->create($arguments)],
            'bulk_create' => $this->bulkCreate($arguments),
            default => ['success' => false, 'message' => "Unknown action: {$action}"],
        };
    }

    private function bulkCreate(array $arguments): array
    {
        $validated = Validator::make($arguments, ['users' => 'required|array|min:1'])->validate();

        $result = $this->userAccountService->bulkCreate($validated['users']);

        return ['success' => true, 'message' => 'Bulk upload completed', ...$result];
    }
}
