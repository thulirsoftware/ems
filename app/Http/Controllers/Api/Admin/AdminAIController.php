<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AI\AIService;
use App\Services\AI\ConversationService;
use Illuminate\Http\Request;

class AdminAIController extends Controller
{
    public function __construct(
        private AIService $aiService,
        private ConversationService $conversationService
    ) {}

    public function createConversation(Request $request)
    {
        $admin = $request->user('admins');

        return response()->json(
            $this->conversationService->createConversation('admin', $admin->id)
        );
    }

    public function conversations(Request $request)
    {
        $admin = $request->user('admins');

        return response()->json(
            $this->conversationService->getConversations('admin', $admin->id)
        );
    }

    public function conversation(Request $request, $conversation)
    {
        $admin = $request->user('admins');

        return response()->json(
            $this->conversationService->getConversation('admin', $admin->id, $conversation)
        );
    }

    public function chat(Request $request, $conversation)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $admin = $request->user('admins');

        $response = $this->aiService->chat(
            (int) $conversation,
            $request->message,
            'admin',
            $admin
        );

        if ($response['success']) {
            return response()->json($response);
        }

        return response()->json([
            'message' => $response['message'] ?? 'AI request failed.',
            'body' => $response['body'] ?? null,
        ], $response['status'] ?? 400);
    }

    public function deleteConversation(Request $request, $conversation)
    {
        $admin = $request->user('admins');

        $this->conversationService->deleteConversation('admin', $admin->id, $conversation);

        return response()->json(['message' => 'Conversation deleted']);
    }
}
