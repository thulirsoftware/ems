<?php

namespace App\Services\AI;

use App\Models\AIConversation;
use Illuminate\Support\Str;

class ConversationService
{
    // $actor is 'admin' or 'user' — the guard that owns the conversation.
    public function createConversation(string $actor, int $actorId): AIConversation
    {
        return AIConversation::create([
            'admin_id' => $actor === 'admin' ? $actorId : null,
            'user_id' => $actor === 'user' ? $actorId : null,
            'title' => 'New Chat',
            'interaction_id' => null,
        ]);
    }

    public function getConversations(string $actor, int $actorId)
    {
        return AIConversation::where($actor === 'admin' ? 'admin_id' : 'user_id', $actorId)
            ->latest()
            ->get([
                'id',
                'title',
                'created_at',
                'updated_at',
            ]);
    }

    public function getConversation(
        string $actor,
        int $actorId,
        int $conversationId
    ) {
        return AIConversation::with([
            'messages' => fn ($query) => $query
                ->select(
                    'id',
                    'conversation_id',
                    'role',
                    'message',
                    'created_at'
                )
                ->orderBy('created_at'),
        ])
            ->where($actor === 'admin' ? 'admin_id' : 'user_id', $actorId)
            ->findOrFail($conversationId);
    }

    public function getInteractionId(int $conversationId): ?string
    {
        return AIConversation::where('id', $conversationId)->value('interaction_id');
    }

    public function saveInteractionId(
        int $conversationId,
        string $interactionId
    ): AIConversation {
        $conversation = AIConversation::findOrFail($conversationId);

        $conversation->update([
            'interaction_id' => $interactionId,
        ]);

        return $conversation;
    }

    public function saveMessages(
        AIConversation $conversation,
        string $userMessage,
        string $assistantMessage
    ): void {
        $conversation->messages()->create([
            'role' => 'user',
            'message' => $userMessage,
        ]);

        $conversation->messages()->create([
            'role' => 'assistant',
            'message' => $assistantMessage,
        ]);
    }

    public function updateTitleIfNeeded(
        AIConversation $conversation,
        ?string $title
    ): void {
        if ($conversation->title !== 'New Chat') {
            return;
        }

        if ($conversation->messages()->count() > 2) {
            return;
        }

        $conversation->update([
            'title' => Str::limit($title ?: 'New Chat', 40),
        ]);
    }

    public function deleteConversation(
        string $actor,
        int $actorId,
        int $conversationId
    ): void {
        AIConversation::where($actor === 'admin' ? 'admin_id' : 'user_id', $actorId)
            ->where('id', $conversationId)
            ->delete();
    }

    public function extractTitle(string &$assistantMessage): ?string
    {
        if (!preg_match('/^\[TITLE\]:\s*(.+)$/m', $assistantMessage, $matches)) {
            return null;
        }

        $assistantMessage = preg_replace(
            '/^\[TITLE\]:\s*.+\R\R?/',
            '',
            $assistantMessage,
            1
        );

        return trim($matches[1]);
    }
}
