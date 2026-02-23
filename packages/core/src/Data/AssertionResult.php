<?php

declare(strict_types=1);

namespace PhpEvals\Core\Data;

final class AssertionResult
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly string $type,
        public readonly bool $passed,
        public readonly ?string $message = null,
        public readonly array $details = [],
    ) {}

    /**
     * @param  array<string, mixed>  $details
     */
    public static function pass(string $type, array $details = []): self
    {
        return new self($type, true, null, $details);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function fail(string $type, string $message, array $details = []): self
    {
        return new self($type, false, $message, $details);
    }
}
