<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public static function notifyUser(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public static function notifyAdmin(
        int $adminId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        return Notification::create([
            'admin_id' => $adminId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public static function markAsRead(int $notificationId): void
    {
        Notification::where('id', $notificationId)
            ->update(['is_read' => true]);
    }

    public static function markAllUserRead(int $userId): void
    {
        Notification::where('user_id', $userId)
            ->update(['is_read' => true]);
    }

    public static function delete(int $notificationId): void
    {
        Notification::where('id', $notificationId)->delete();
    }
}
