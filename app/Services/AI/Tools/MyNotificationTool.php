<?php

namespace App\Services\AI\Tools;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class MyNotificationTool implements AITool
{
    public function actors(): array
    {
        return ['user'];
    }

    public function metadata(): array
    {
        return [
            'name' => 'my_notifications',
            'description' => 'View the current student\'s unread notifications, or mark them all as read.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'action' => ['type' => 'STRING', 'enum' => ['list', 'mark_all_read']],
                ],
                'required' => ['action'],
            ],
            'actions' => [
                'list — unread notifications, newest first.',
                'mark_all_read — mark every notification as read.',
            ],
            'instructions' => [],
        ];
    }

    public function execute(array $arguments): mixed
    {
        $user = Auth::guard('users')->user();
        $action = $arguments['action'] ?? null;

        return match ($action) {
            'list' => ['success' => true, 'data' => NotificationService::listUnreadForUser($user->id)],
            'mark_all_read' => (function () use ($user) {
                NotificationService::markAllUserRead($user->id);

                return ['success' => true, 'message' => 'All notifications marked as read'];
            })(),
            default => ['success' => false, 'message' => "Unknown action: {$action}"],
        };
    }
}
