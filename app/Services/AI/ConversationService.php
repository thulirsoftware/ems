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
        // Most recently active first, so a conversation jumps to the top when it's used.
        return AIConversation::where($actor === 'admin' ? 'admin_id' : 'user_id', $actorId)
            ->latest('updated_at')
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

    public function getInteractionId(
        string $actor,
        int $actorId,
        int $conversationId
    ): ?string {
        return AIConversation::where($actor === 'admin' ? 'admin_id' : 'user_id', $actorId)
            ->where('id', $conversationId)
            ->firstOrFail()
            ->interaction_id;
    }

    public function saveInteractionId(
        string $actor,
        int $actorId,
        int $conversationId,
        string $interactionId
    ): AIConversation {
        $conversation = AIConversation::where($actor === 'admin' ? 'admin_id' : 'user_id', $actorId)
            ->where('id', $conversationId)
            ->firstOrFail();

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
        if (!preg_match('/^\[TITLE\]:[ \t]*(.+)$/m', $assistantMessage, $matches)) {
            return null;
        }

        $assistantMessage = trim(preg_replace(
            '/^\[TITLE\]:[ \t]*.+(\R|$)\R?/m',
            '',
            $assistantMessage,
            1
        ));

        return trim($matches[1]);
    }
}
