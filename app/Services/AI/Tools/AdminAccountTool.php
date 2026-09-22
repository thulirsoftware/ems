<?php

namespace App\Services\AI\Tools;

use App\Services\AdminAccountService;

class AdminAccountTool implements AITool
{
    public function __construct(
        private AdminAccountService $adminAccountService
    ) {}

    public function actors(): array
    {
        return ['admin'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'manage_admin_accounts',
            'description' => 'Register a new administrator account.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'action' => ['type' => 'STRING', 'enum' => ['create']],
                    'name' => ['type' => 'STRING'],
                    'email' => ['type' => 'STRING'],
                    'password' => ['type' => 'STRING', 'description' => 'Minimum 8 characters.'],
                ],
                'required' => ['action'],
            ],
            'actions' => [
                'create — register a new administrator account with full administrative access.',
            ],
            'instructions' => [
                'This grants the new account the same full administrative access as the current administrator — always confirm explicitly before calling it.',
                'Never reveal the entered password back; only confirm the account was created.',
            ],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $action = $arguments['action'] ?? null;

        return match ($action) {
            'create' => ['success' => true, 'message' => 'Administrator account created', 'data' => $this->adminAccountService->create($arguments)],
            default => ['success' => false, 'message' => "Unknown action: {$action}"],
        };
    }
}
