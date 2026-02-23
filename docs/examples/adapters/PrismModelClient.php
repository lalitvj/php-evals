<?php

declare(strict_types=1);

namespace App\AI;

use EchoLabs\Prism\Prism;
use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;

/**
 * Prism adapter for php-evals.
 *
 * Works with any provider Prism supports: OpenAI, Anthropic, Ollama,
 * Gemini, Mistral, xAI, DeepSeek, and more.
 *
 * Requires: composer require echolabsdev/prism
 *
 * Usage in config/ai-evals.php:
 *   'model_client' => \App\AI\PrismModelClient::class,
 *   'model_client_options' => [
 *       'provider' => 'anthropic',   // Any Prism provider name
 *       'model'    => 'claude-sonnet-4-20250514',
 *   ],
 */
final class PrismModelClient implements ModelClient
{
    private string $provider;

    private string $model;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $this->provider = $options['provider'] ?? 'openai';
        $this->model = $options['model'] ?? 'gpt-4o';
    }

    public function complete(ModelRequest $request): ModelResponse
    {
        $model = $request->model ?? $this->model;

        $response = Prism::text()
            ->using($this->provider, $model)
            ->withPrompt($request->input)
            ->generate();

        return new ModelResponse(
            output: $response->text ?? '',
            promptTokens: $response->usage->promptTokens ?? 0,
            completionTokens: $response->usage->completionTokens ?? 0,
        );
    }
}
