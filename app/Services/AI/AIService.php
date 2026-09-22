<?php

namespace App\Services\AI;

use App\Models\Admin;
use App\Models\User;

class AIService
{
    public function __construct(
        private GeminiService $geminiService,
        private ToolRegistry $toolRegistry,
        private ToolExecutor $toolExecutor,
        private PromptService $promptService,
        private ConversationService $conversationService
    ) {}

    // $actor is 'admin' or 'user' — which guard authenticated this request.
    public function chat(
        int $conversationId,
        string $message,
        string $actor,
        Admin|User $actorModel
    ): array {
        $previousInteractionId = $this->conversationService
            ->getInteractionId($conversationId);

        $input = [];

        if (!$previousInteractionId) {
            $input[] = [
                'type' => 'text',
                'text' => $this->promptService->systemPrompt($actor, $actorModel),
            ];
        }

        $input[] = [
            'type' => 'text',
            'text' => $message,
        ];

        $interaction = $this->geminiService->createInteraction(
            input: $input,
            tools: $this->toolRegistry->geminiTools($actor),
            previousInteractionId: $previousInteractionId
        );

        if (!$interaction['success']) {
            return $this->handleGeminiError($interaction);
        }

        $body = $interaction['body'];

        $maxIterations = 10;
        $iterations = 0;

        while ($this->geminiService->requiresAction($body)) {
            $iterations++;

            if ($iterations > $maxIterations) {
                return [
                    'success' => false,
                    'status' => 500,
                    'message' => 'Maximum tool execution limit reached.',
                ];
            }

            $functionCall = $this->geminiService->functionCall($body);

            if (!$functionCall) {
                return [
                    'success' => false,
                    'status' => 500,
                    'message' => 'Gemini requested an action but no function call was found.',
                ];
            }

            $toolResult = $this->toolExecutor->execute(
                $functionCall['name'],
                $functionCall['arguments'],
                $actor
            );

            $toolResult = json_decode(
                json_encode(
                    $toolResult,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                true
            );

            $interaction = $this->geminiService->continueInteraction(
                interactionId: $this->geminiService->interactionId($body),
                callId: $functionCall['id'],
                functionName: $functionCall['name'],
                result: $toolResult,
                tools: $this->toolRegistry->geminiTools($actor)
            );

            if (!$interaction['success']) {
                return $this->handleGeminiError($interaction);
            }

            $body = $interaction['body'];
        }

        if (!$this->geminiService->completed($body)) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Unknown interaction status.',
                'body' => $body,
            ];
        }

        $conversation = $this->conversationService->saveInteractionId(
            $conversationId,
            $this->geminiService->interactionId($body)
        );

        $assistantMessage = $this->geminiService->outputText($body);

        $title = null;

        if (!$previousInteractionId) {
            $title = $this->conversationService->extractTitle($assistantMessage);
        }

        $this->conversationService->saveMessages(
            $conversation,
            $message,
            $assistantMessage
        );

        $this->conversationService->updateTitleIfNeeded($conversation, $title);

        return [
            'success' => true,
            'message' => $assistantMessage,
            'usage' => $this->geminiService->usage($body),
            'interaction_id' => $this->geminiService->interactionId($body),
        ];
    }

    private function handleGeminiError(array $interaction): array
    {
        $status = $interaction['status'] ?? 500;

        return [
            'success' => false,
            'status' => $status,

            'message' => match ($status) {
                400 => 'The AI request is invalid.',
                401 => 'The AI API key is invalid.',
                403 => 'Access to the AI service was denied.',
                404 => 'The selected AI model was not found.',
                408 => 'The AI request timed out.',
                429 => 'The AI service has reached its usage limit. Please try again in a minute or later today.',
                500 => 'The AI service encountered an internal error.',
                502, 503, 504 => 'The AI service is temporarily unavailable. Please try again later.',
                default => data_get(
                    $interaction,
                    'body.error.message',
                    'An unexpected AI error occurred.'
                ),
            },

            'body' => $interaction['body'] ?? null,
        ];
    }
}
