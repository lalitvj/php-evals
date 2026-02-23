<?php

declare(strict_types=1);

namespace App\AI;

use GuzzleHttp\Client;
use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Data\ToolCall;

/**
 * Google Gemini adapter for php-evals.
 *
 * Usage in config/ai-evals.php:
 *   'model_client' => \App\AI\GeminiModelClient::class,
 *   'model_client_options' => [
 *       'api_key' => env('GEMINI_API_KEY'),
 *       'model'   => 'gemini-2.0-flash',
 *   ],
 */
final class GeminiModelClient implements ModelClient
{
    private Client $http;

    private string $model;

    private string $apiKey;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $this->apiKey = $options['api_key'] ?? env('GEMINI_API_KEY', '');
        $this->model = $options['model'] ?? 'gemini-2.0-flash';

        $this->http = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => (int) ($options['timeout'] ?? 120),
        ]);
    }

    public function complete(ModelRequest $request): ModelResponse
    {
        $model = $request->model ?? $this->model;

        $body = [
            'contents' => [
                ['parts' => [['text' => $request->input]]],
            ],
        ];

        $url = sprintf('models/%s:generateContent?key=%s', $model, $this->apiKey);
        $response = $this->http->post($url, ['json' => $body]);
        $data = json_decode($response->getBody()->getContents(), true);

        $candidate = $data['candidates'][0] ?? [];
        $parts = $candidate['content']['parts'] ?? [];

        $output = '';
        $toolCalls = [];

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $output .= $part['text'];
            }

            if (isset($part['functionCall'])) {
                $toolCalls[] = new ToolCall(
                    $part['functionCall']['name'] ?? '',
                    is_array($part['functionCall']['args'] ?? null) ? $part['functionCall']['args'] : [],
                );
            }
        }

        $usage = $data['usageMetadata'] ?? [];
        $promptTokens = (int) ($usage['promptTokenCount'] ?? 0);
        $completionTokens = (int) ($usage['candidatesTokenCount'] ?? 0);

        return new ModelResponse(
            output: $output,
            toolCalls: $toolCalls,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            cost: 0.0,
            raw: $data,
        );
    }
}
