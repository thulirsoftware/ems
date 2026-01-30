<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user('users');

        $notifications = Notification::where('user_id', $user->id)
            ->where('is_read', 'false')
            ->latest()
            ->get();

        return response()->json($notifications);
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user('users');

        NotificationService::markAllUserRead($user->id);

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }
}
