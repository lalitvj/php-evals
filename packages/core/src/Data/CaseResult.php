<?php

declare(strict_types=1);

namespace PhpEvals\Core\Data;

final class CaseResult
{
    /**
     * @param  array<int, AssertionResult>  $assertionResults
     */
    public function __construct(
        public readonly string $caseId,
        public readonly array $assertionResults,
    ) {}

    public function passed(): bool
    {
        foreach ($this->assertionResults as $result) {
            if (! $result->passed) {
                return false;
            }
        }

        return true;
    }
}
