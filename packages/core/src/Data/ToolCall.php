<?php

declare(strict_types=1);

namespace PhpEvals\Core\Data;

final class ToolCall
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public function __construct(
        public readonly string $name,
        public readonly array $arguments = [],
    ) {}

    /**
     * @param  array{name?: mixed, arguments?: mixed}  $payload
     */
    public static function fromArray(array $payload): self
    {
        $name = is_string($payload['name'] ?? null) ? $payload['name'] : '';
        $arguments = is_array($payload['arguments'] ?? null) ? $payload['arguments'] : [];

        return new self($name, $arguments);
    }
}
