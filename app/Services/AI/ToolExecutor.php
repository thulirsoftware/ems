<?php

namespace App\Services\AI;

use Illuminate\Validation\ValidationException;
use Throwable;

class ToolExecutor
{
    public function __construct(
        private ToolRegistry $toolRegistry
    ) {}

    public function execute(
        string $toolName,
        array $arguments,
        string $actor
    ): mixed {
        $tool = $this->toolRegistry->find($toolName, $actor);

        if (!$tool) {
            return [
                'success' => false,
                'error' => true,
                'message' => "Unknown AI tool: {$toolName}.",
            ];
        }

        try {
            return $tool->execute($arguments);
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'error' => true,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}
