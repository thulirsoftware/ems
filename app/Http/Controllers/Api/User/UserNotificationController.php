<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            NotificationService::listUnreadForUser($request->user('users')->id)
        );
    }

    public function markAllRead(Request $request)
    {
        NotificationService::markAllUserRead($request->user('users')->id);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }
}
