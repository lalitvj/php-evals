<?php

declare(strict_types=1);

namespace PhpEvals\Core\Data;

final class ModelResponse
{
    /**
     * @param  array<int, ToolCall>  $toolCalls
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $output,
        public readonly array $toolCalls = [],
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array{output?: mixed, tool_calls?: mixed, raw?: mixed}  $payload
     */
    public static function fromArray(array $payload): self
    {
        $toolCalls = [];

        if (is_array($payload['tool_calls'] ?? null)) {
            foreach ($payload['tool_calls'] as $toolCall) {
                if (is_array($toolCall)) {
                    $toolCalls[] = ToolCall::fromArray($toolCall);
                }
            }
        }

        return new self(
            is_string($payload['output'] ?? null) ? $payload['output'] : '',
            $toolCalls,
            is_array($payload['raw'] ?? null) ? $payload['raw'] : [],
        );
    }
}
