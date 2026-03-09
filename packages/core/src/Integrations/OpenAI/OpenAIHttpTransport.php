<?php

declare(strict_types=1);

namespace PhpEvals\Core\Integrations\OpenAI;

use PhpEvals\Core\Exceptions\RuntimeConfigurationException;

final class OpenAIHttpTransport
{
    /** @var null|callable(string, array<string, mixed>, array<string, string>): array<string, mixed> */
    private readonly mixed $requestHandler;

    private readonly string $apiKey;

    private readonly string $baseUrl;

    private readonly int $timeout;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $this->requestHandler = is_callable($options['request_handler'] ?? null)
            ? $options['request_handler']
            : null;
        $this->apiKey = is_string($options['api_key'] ?? null) ? $options['api_key'] : '';
        $this->baseUrl = is_string($options['base_url'] ?? null) && $options['base_url'] !== ''
            ? rtrim($options['base_url'], '/').'/'
            : 'https://api.openai.com/v1/';
        $this->timeout = is_int($options['timeout'] ?? null) && $options['timeout'] > 0
            ? $options['timeout']
            : 120;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->resolveApiKey(),
            'Content-Type' => 'application/json',
        ];

        if (is_callable($this->requestHandler)) {
            $response = ($this->requestHandler)($path, $payload, $headers);

            if (! is_array($response)) {
                throw new RuntimeConfigurationException('OpenAI request_handler must return an array payload.');
            }

            return $response;
        }

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (! is_string($json)) {
            throw new RuntimeConfigurationException('Unable to encode OpenAI request payload.');
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $this->headersAsString($headers),
                'content' => $json,
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($this->baseUrl.ltrim($path, '/'), false, $context);
        $responseHeaders = is_array($http_response_header ?? null) ? $http_response_header : [];
        $statusCode = $this->parseStatusCode($responseHeaders);

        if (! is_string($body) || $body === '') {
            throw new RuntimeConfigurationException(sprintf('OpenAI request to %s returned an empty response.', $path));
        }

        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            throw new RuntimeConfigurationException(sprintf('OpenAI request to %s returned invalid JSON.', $path));
        }

        if ($statusCode >= 400) {
            $message = is_string($decoded['error']['message'] ?? null)
                ? $decoded['error']['message']
                : sprintf('HTTP %d', $statusCode);

            throw new RuntimeConfigurationException(sprintf('OpenAI request failed: %s', $message));
        }

        return $decoded;
    }

    private function resolveApiKey(): string
    {
        if ($this->apiKey !== '') {
            return $this->apiKey;
        }

        $apiKey = getenv('OPENAI_API_KEY');
        if (is_string($apiKey) && $apiKey !== '') {
            return $apiKey;
        }

        throw new RuntimeConfigurationException('OpenAI integration requires an api_key option or OPENAI_API_KEY env var.');
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function headersAsString(array $headers): string
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name.': '.$value;
        }

        return implode("\r\n", $lines);
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function parseStatusCode(array $headers): int
    {
        $statusLine = $headers[0] ?? '';
        if (! is_string($statusLine) || ! preg_match('/\s(\d{3})\s/', $statusLine, $matches)) {
            return 200;
        }

        return (int) $matches[1];
    }
}
