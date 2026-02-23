<?php

declare(strict_types=1);

namespace App\AI;

use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;

use function Laravel\Ai\agent;

/**
 * Laravel AI SDK adapter for php-evals.
 *
 * Works with any provider configured in Laravel AI SDK (OpenAI, Anthropic, Gemini, etc.).
 *
 * Requires: composer require laravel/ai
 *
 * Usage in config/ai-evals.php:
 *   'model_client' => \App\AI\LaravelAIModelClient::class,
 *   'model_client_options' => [
 *       'instructions' => 'You are a helpful assistant.',
 *   ],
 *
 * Or use a dedicated Agent class:
 *   'model_client' => \App\AI\LaravelAIModelClient::class,
 *   'model_client_options' => [
 *       'agent_class' => \App\AI\Agents\SupportAgent::class,
 *   ],
 */
final class LaravelAIModelClient implements ModelClient
{
    private string $instructions;

    private ?string $agentClass;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $this->instructions = $options['instructions'] ?? 'You are a helpful assistant.';
        $this->agentClass = $options['agent_class'] ?? null;
    }

    public function complete(ModelRequest $request): ModelResponse
    {
        if ($this->agentClass !== null && class_exists($this->agentClass)) {
            $response = (new ($this->agentClass))->prompt($request->input);
        } else {
            $response = agent(
                instructions: $this->instructions,
            )->prompt($request->input);
        }

        $text = (string) $response;

        $promptTokens = 0;
        $completionTokens = 0;
        if (isset($response->usage)) {
            $promptTokens = $response->usage->inputTokens ?? 0;
            $completionTokens = $response->usage->outputTokens ?? 0;
        }

        return new ModelResponse(
            output: $text,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
        );
    }
}
