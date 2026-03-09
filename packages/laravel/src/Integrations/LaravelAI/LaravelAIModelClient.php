<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Integrations\LaravelAI;

use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;

use function Laravel\Ai\agent;

final class LaravelAIModelClient implements ModelClient
{
    private readonly string $instructions;

    private readonly ?string $agentClass;

    /** @var null|callable(ModelRequest, array<string, mixed>): ModelResponse|array<string, mixed>|string */
    private readonly mixed $responseHandler;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(private readonly array $options = [])
    {
        $this->instructions = is_string($options['instructions'] ?? null) && $options['instructions'] !== ''
            ? $options['instructions']
            : 'You are a helpful assistant.';
        $this->agentClass = is_string($options['agent_class'] ?? null) && $options['agent_class'] !== ''
            ? $options['agent_class']
            : null;
        $this->responseHandler = is_callable($options['response_handler'] ?? null)
            ? $options['response_handler']
            : null;
    }

    public function complete(ModelRequest $request): ModelResponse
    {
        if (is_callable($this->responseHandler)) {
            return $this->normalizeResponse(($this->responseHandler)($request, $this->options));
        }

        if ($this->agentClass !== null) {
            if (! class_exists($this->agentClass)) {
                throw new RuntimeConfigurationException(sprintf('Configured Laravel AI agent class "%s" was not found.', $this->agentClass));
            }

            $response = (new ($this->agentClass))->prompt($request->input);

            return $this->normalizeRuntimeResponse($response);
        }

        if (! function_exists('\\Laravel\\Ai\\agent')) {
            throw new RuntimeConfigurationException('Laravel AI integration requires the laravel/ai package.');
        }

        $response = agent(
            instructions: is_string($request->metadata['instructions'] ?? null)
                ? $request->metadata['instructions']
                : $this->instructions,
        )->prompt($request->input);

        return $this->normalizeRuntimeResponse($response);
    }

    /**
     * @param  ModelResponse|array<string, mixed>|string  $response
     */
    private function normalizeResponse(mixed $response): ModelResponse
    {
        if ($response instanceof ModelResponse) {
            return $response;
        }

        if (is_array($response)) {
            return ModelResponse::fromArray($response);
        }

        if (is_string($response)) {
            return new ModelResponse($response);
        }

        throw new RuntimeConfigurationException('Laravel AI response_handler must return ModelResponse, array payload, or string.');
    }

    private function normalizeRuntimeResponse(mixed $response): ModelResponse
    {
        if (! is_object($response)) {
            return $this->normalizeResponse($response);
        }

        $usage = is_object($response->usage ?? null) ? $response->usage : null;

        return new ModelResponse(
            output: (string) $response,
            promptTokens: is_int($usage->inputTokens ?? null) ? $usage->inputTokens : 0,
            completionTokens: is_int($usage->outputTokens ?? null) ? $usage->outputTokens : 0,
        );
    }
}
