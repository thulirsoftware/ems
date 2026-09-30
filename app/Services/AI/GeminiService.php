<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
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

        return $this->send($payload);
    }

    // $results holds one ['id', 'name', 'result'] entry per function call the
    // model requested in the previous step — all of them must be answered together.
    public function continueInteraction(
        string $interactionId,
        array $results,
        array $tools = []
    ): array {
        $payload = [
            'model' => str_starts_with($this->model, 'models/')
                ? $this->model
                : 'models/'.$this->model,

            'previous_interaction_id' => $interactionId,

            'input' => array_map(fn (array $result) => [
                'type' => 'function_result',
                'name' => $result['name'],
                'call_id' => $result['id'],
                'result' => [
                    [
                        'type' => 'text',
                        'text' => json_encode(
                            $result['result'],
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        ),
                    ],
                ],
            ], $results),
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        return $this->send($payload);
    }

    private function send(array $payload): array
    {
        try {
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
        } catch (ConnectionException $e) {
            // Network failure or timeout — surface it like a gateway timeout
            // instead of letting it bubble up as an unhandled 500.
            return [
                'success' => false,
                'status' => 504,
                'body' => ['error' => ['message' => $e->getMessage()]],
            ];
        }

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

    // A reply can be split across several text parts, so join them all.
    public function outputText(array $body): ?string
    {
        $parts = [];

        foreach (data_get($body, 'steps', []) as $step) {
            if (($step['type'] ?? null) !== 'model_output') {
                continue;
            }

            foreach ($step['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'text' && ($content['text'] ?? '') !== '') {
                    $parts[] = $content['text'];
                }
            }
        }

        return $parts ? implode("\n\n", $parts) : null;
    }

    // Every function call requested in this step — the model can ask for
    // several tools in parallel.
    public function functionCalls(array $body): array
    {
        $calls = [];

        foreach (data_get($body, 'steps', []) as $step) {
            if (($step['type'] ?? null) !== 'function_call') {
                continue;
            }

            $calls[] = [
                'id' => $step['id'] ?? null,
                'name' => $step['name'] ?? null,
                'arguments' => (array) ($step['arguments'] ?? []),
            ];
        }

        return $calls;
    }

    public function usage(array $body): array
    {
        return data_get($body, 'usage', []);
    }
}
