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
            ->getInteractionId($actor, $actorModel->id, $conversationId);

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

            $functionCalls = $this->geminiService->functionCalls($body);

            if (!$functionCalls) {
                return [
                    'success' => false,
                    'status' => 500,
                    'message' => 'Gemini requested an action but no function call was found.',
                ];
            }

            // Gemini may request several tools in parallel; every call must be
            // answered in the same follow-up request.
            $results = [];

            foreach ($functionCalls as $functionCall) {
                $results[] = [
                    'id' => $functionCall['id'],
                    'name' => $functionCall['name'],
                    'result' => $this->toolExecutor->execute(
                        $functionCall['name'],
                        $functionCall['arguments'],
                        $actor
                    ),
                ];
            }

            $interaction = $this->geminiService->continueInteraction(
                interactionId: $this->geminiService->interactionId($body),
                results: $results,
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
            $actor,
            $actorModel->id,
            $conversationId,
            $this->geminiService->interactionId($body)
        );

        $assistantMessage = $this->geminiService->outputText($body)
            ?? 'Sorry, I could not generate a response. Please try again.';

        // Always strip a [TITLE] line so it never leaks into the chat, but only
        // use it to name the conversation on the first exchange.
        $title = $this->conversationService->extractTitle($assistantMessage);

        if ($previousInteractionId) {
            $title = null;
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

            // Never pass Gemini's own status through: a 401 from an invalid
            // API key would make the frontend think the user's session expired
            // and log them out. Report upstream failures as gateway errors.
            'status' => match ($status) {
                429 => 429,
                408, 504 => 504,
                503 => 503,
                default => 502,
            },

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
