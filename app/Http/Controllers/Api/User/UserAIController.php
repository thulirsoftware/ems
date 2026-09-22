<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\AI\AIService;
use App\Services\AI\ConversationService;
use Illuminate\Http\Request;

class UserAIController extends Controller
{
    public function __construct(
        private AIService $aiService,
        private ConversationService $conversationService
    ) {}

    public function createConversation(Request $request)
    {
        $user = $request->user('users');

        return response()->json(
            $this->conversationService->createConversation('user', $user->id)
        );
    }

    public function conversations(Request $request)
    {
        $user = $request->user('users');

        return response()->json(
            $this->conversationService->getConversations('user', $user->id)
        );
    }

    public function conversation(Request $request, $conversation)
    {
        $user = $request->user('users');

        return response()->json(
            $this->conversationService->getConversation('user', $user->id, $conversation)
        );
    }

    public function chat(Request $request, $conversation)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = $request->user('users');

        $response = $this->aiService->chat(
            (int) $conversation,
            $request->message,
            'user',
            $user
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
        $user = $request->user('users');

        $this->conversationService->deleteConversation('user', $user->id, $conversation);

        return response()->json(['message' => 'Conversation deleted']);
    }
}
