<?php

declare(strict_types=1);

namespace App\AI;

use GuzzleHttp\Client;
use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Data\ToolCall;

/**
 * Anthropic Messages API adapter for php-evals.
 *
 * Usage in config/ai-evals.php:
 *   'model_client' => \App\AI\AnthropicModelClient::class,
 *   'model_client_options' => [
 *       'api_key' => env('ANTHROPIC_API_KEY'),
 *       'model'   => 'claude-sonnet-4-20250514',
 *   ],
 */
final class AnthropicModelClient implements ModelClient
{
    private Client $http;

    private string $model;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $apiKey = $options['api_key'] ?? env('ANTHROPIC_API_KEY', '');
        $this->model = $options['model'] ?? 'claude-sonnet-4-20250514';

        $this->http = new Client([
            'base_uri' => 'https://api.anthropic.com/v1/',
            'headers' => [
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
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
            'max_tokens' => 4096,
            'messages' => [
                ['role' => 'user', 'content' => $request->input],
            ],
        ];

        $systemPrompt = $request->metadata['system_prompt'] ?? null;
        if (is_string($systemPrompt) && $systemPrompt !== '') {
            $body['system'] = $systemPrompt;
        }

        $response = $this->http->post('messages', ['json' => $body]);
        $data = json_decode($response->getBody()->getContents(), true);

        $output = '';
        $toolCalls = [];

        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $output .= $block['text'] ?? '';
            }

            if (($block['type'] ?? '') === 'tool_use') {
                $toolCalls[] = new ToolCall(
                    $block['name'] ?? '',
                    is_array($block['input'] ?? null) ? $block['input'] : [],
                );
            }
        }

        $usage = $data['usage'] ?? [];
        $inputTokens = (int) ($usage['input_tokens'] ?? 0);
        $outputTokens = (int) ($usage['output_tokens'] ?? 0);

        return new ModelResponse(
            output: $output,
            toolCalls: $toolCalls,
            promptTokens: $inputTokens,
            completionTokens: $outputTokens,
            cost: $this->estimateCost($model, $inputTokens, $outputTokens),
            raw: $data,
        );
    }

    private function estimateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        // Prices per 1M tokens (input / output) as of early 2026.
        $pricing = [
            'claude-sonnet-4-20250514' => [3.00, 15.00],
            'claude-haiku-4-5-20251001' => [0.80, 4.00],
            'claude-opus-4-20250514' => [15.00, 75.00],
        ];

        [$inputRate, $outputRate] = $pricing[$model] ?? [0.0, 0.0];

        return ($inputTokens * $inputRate / 1_000_000) + ($outputTokens * $outputRate / 1_000_000);
    }
}
