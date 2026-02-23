<?php

declare(strict_types=1);

namespace PhpEvals\Core\Data;

final class EvalCase
{
    /**
     * @param  array<string, mixed>  $expected
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $input,
        public readonly array $expected,
        public readonly array $metadata = [],
    ) {}
}
