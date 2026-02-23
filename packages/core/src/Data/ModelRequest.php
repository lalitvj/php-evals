<?php

declare(strict_types=1);

namespace PhpEvals\Core\Data;

final class ModelRequest
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $caseId,
        public readonly string $input,
        public readonly ?string $model = null,
        public readonly array $metadata = [],
    ) {}
}
