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
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly float $cost = 0.0,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array{output?: mixed, tool_calls?: mixed, prompt_tokens?: mixed, completion_tokens?: mixed, cost?: mixed, raw?: mixed}  $payload
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

        $promptTokens = self::toNonNegativeInt($payload['prompt_tokens'] ?? 0);
        $completionTokens = self::toNonNegativeInt($payload['completion_tokens'] ?? 0);
        $cost = self::toNonNegativeFloat($payload['cost'] ?? 0.0);

        return new self(
            is_string($payload['output'] ?? null) ? $payload['output'] : '',
            $toolCalls,
            $promptTokens,
            $completionTokens,
            $cost,
            is_array($payload['raw'] ?? null) ? $payload['raw'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $toolCalls = [];
        foreach ($this->toolCalls as $toolCall) {
            $toolCalls[] = [
                'name' => $toolCall->name,
                'arguments' => $toolCall->arguments,
            ];
        }

        return [
            'output' => $this->output,
            'tool_calls' => $toolCalls,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'cost' => $this->cost,
            'raw' => $this->raw,
        ];
    }

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }

    private static function toNonNegativeInt(mixed $value): int
    {
        if (is_int($value) || is_float($value)) {
            return max(0, (int) $value);
        }

        return 0;
    }

    private static function toNonNegativeFloat(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return max(0.0, (float) $value);
        }

        return 0.0;
    }
}
