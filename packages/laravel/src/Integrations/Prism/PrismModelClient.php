<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Integrations\Prism;

use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;

final class PrismModelClient implements ModelClient
{
    private readonly string $provider;

    private readonly string $model;

    /** @var null|callable(ModelRequest, array<string, mixed>): ModelResponse|array<string, mixed>|string */
    private readonly mixed $responseHandler;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(private readonly array $options = [])
    {
        $this->provider = is_string($options['provider'] ?? null) && $options['provider'] !== ''
            ? $options['provider']
            : 'openai';
        $this->model = is_string($options['model'] ?? null) && $options['model'] !== ''
            ? $options['model']
            : 'gpt-4o';
        $this->responseHandler = is_callable($options['response_handler'] ?? null)
            ? $options['response_handler']
            : null;
    }

    public function complete(ModelRequest $request): ModelResponse
    {
        if (is_callable($this->responseHandler)) {
            return $this->normalizeResponse(($this->responseHandler)($request, $this->options));
        }

        $prismClass = 'EchoLabs\\Prism\\Prism';
        if (! class_exists($prismClass)) {
            throw new RuntimeConfigurationException('Prism integration requires the echolabsdev/prism package.');
        }

        $builder = $prismClass::text()
            ->using($this->provider, $request->model ?? $this->model)
            ->withPrompt($request->input);

        $systemPrompt = $request->metadata['system_prompt'] ?? ($this->options['system_prompt'] ?? null);
        if (is_string($systemPrompt) && $systemPrompt !== '' && method_exists($builder, 'withSystemPrompt')) {
            $builder = $builder->withSystemPrompt($systemPrompt);
        }

        $response = $builder->generate();
        $usage = is_object($response->usage ?? null) ? $response->usage : null;

        return new ModelResponse(
            output: is_string($response->text ?? null) ? $response->text : (string) ($response->text ?? ''),
            promptTokens: is_int($usage->promptTokens ?? null) ? $usage->promptTokens : 0,
            completionTokens: is_int($usage->completionTokens ?? null) ? $usage->completionTokens : 0,
        );
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

        throw new RuntimeConfigurationException('Prism response_handler must return ModelResponse, array payload, or string.');
    }
}
