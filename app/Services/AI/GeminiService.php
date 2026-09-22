<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    private string $apiKey;

    private string $model;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->model = config('services.gemini.model');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    }

    public function createInteraction(
        mixed $input,
        array $tools = [],
        ?string $previousInteractionId = null
    ): array {
        $payload = [
            'model' => str_starts_with($this->model, 'models/')
                ? $this->model
                : 'models/'.$this->model,
            'input' => $input,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        if ($previousInteractionId) {
            $payload['previous_interaction_id'] = $previousInteractionId;
        }

        $response = Http::acceptJson()
            ->contentType('application/json')
            ->withHeaders([
                'X-goog-api-key' => $this->apiKey,
            ])
            ->timeout(120)
            ->post(
                "{$this->baseUrl}/interactions",
                $payload
            );

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json(),
        ];
    }

    public function continueInteraction(
        string $interactionId,
        string $callId,
        string $functionName,
        mixed $result,
        array $tools = []
    ): array {
        $payload = [
            'model' => str_starts_with($this->model, 'models/')
                ? $this->model
                : 'models/'.$this->model,

            'previous_interaction_id' => $interactionId,

            'input' => [
                [
                    'type' => 'function_result',
                    'name' => $functionName,
                    'call_id' => $callId,
                    'result' => [
                        [
                            'type' => 'text',
                            'text' => json_encode(
                                $result,
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            ),
                        ],
                    ],
                ],
            ],
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        $response = Http::acceptJson()
            ->contentType('application/json')
            ->withHeaders([
                'X-goog-api-key' => $this->apiKey,
            ])
            ->timeout(120)
            ->post(
                "{$this->baseUrl}/interactions",
                $payload
            );

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json(),
        ];
    }

    public function interactionId(array $body): ?string
    {
        return data_get($body, 'id');
    }

    public function status(array $body): ?string
    {
        return data_get($body, 'status');
    }

    public function completed(array $body): bool
    {
        return $this->status($body) === 'completed';
    }

    public function requiresAction(array $body): bool
    {
        return $this->status($body) === 'requires_action';
    }

    public function outputText(array $body): ?string
    {
        foreach (data_get($body, 'steps', []) as $step) {
            if (($step['type'] ?? null) !== 'model_output') {
                continue;
            }

            foreach ($step['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'text') {
                    return $content['text'];
                }
            }
        }

        return null;
    }

    public function functionCall(array $body): ?array
    {
        foreach (data_get($body, 'steps', []) as $step) {
            if (($step['type'] ?? null) !== 'function_call') {
                continue;
            }

            return [
                'id' => $step['id'] ?? null,
                'name' => $step['name'] ?? null,
                'arguments' => (array) ($step['arguments'] ?? []),
            ];
        }

        return null;
    }

    public function usage(array $body): array
    {
        return data_get($body, 'usage', []);
    }
}
