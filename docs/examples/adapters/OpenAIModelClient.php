<?php

declare(strict_types=1);

namespace App\AI;

use GuzzleHttp\Client;
use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Data\ToolCall;

/**
 * OpenAI Chat Completions adapter for php-evals.
 *
 * Usage in config/ai-evals.php:
 *   'model_client' => \App\AI\OpenAIModelClient::class,
 *   'model_client_options' => [
 *       'api_key' => env('OPENAI_API_KEY'),
 *       'model'   => 'gpt-4o',
 *   ],
 */
final class OpenAIModelClient implements ModelClient
{
    private Client $http;

    private string $model;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $apiKey = $options['api_key'] ?? env('OPENAI_API_KEY', '');
        $this->model = $options['model'] ?? 'gpt-4o';

        $this->http = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => (int) ($options['timeout'] ?? 120),
        ]);
    }

    public function complete(ModelRequest $request): ModelResponse
    {
        $model = $request->model ?? $this->model;

        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $request->input],
            ],
        ];

        if ($request->seed !== null) {
            $body['seed'] = $request->seed;
        }

        $response = $this->http->post('chat/completions', ['json' => $body]);
        $data = json_decode($response->getBody()->getContents(), true);

        $choice = $data['choices'][0] ?? [];
        $message = $choice['message'] ?? [];
        $usage = $data['usage'] ?? [];

        $output = $message['content'] ?? '';

        $toolCalls = [];
        foreach ($message['tool_calls'] ?? [] as $tc) {
            $fn = $tc['function'] ?? [];
            $args = json_decode($fn['arguments'] ?? '{}', true) ?: [];
            $toolCalls[] = new ToolCall($fn['name'] ?? '', $args);
        }

        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);

        return new ModelResponse(
            output: is_string($output) ? $output : '',
            toolCalls: $toolCalls,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            cost: $this->estimateCost($model, $promptTokens, $completionTokens),
            raw: $data,
        );
    }

    private function estimateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        // Prices per 1M tokens (input / output) as of early 2026.
        $pricing = [
            'gpt-4o' => [2.50, 10.00],
            'gpt-4o-mini' => [0.15, 0.60],
            'gpt-4-turbo' => [10.00, 30.00],
            'gpt-3.5-turbo' => [0.50, 1.50],
        ];

        [$inputRate, $outputRate] = $pricing[$model] ?? [0.0, 0.0];

        return ($promptTokens * $inputRate / 1_000_000) + ($completionTokens * $outputRate / 1_000_000);
    }
}
