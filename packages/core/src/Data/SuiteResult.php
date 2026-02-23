<?php

declare(strict_types=1);

namespace PhpEvals\Core\Data;

final class SuiteResult
{
    /**
     * @param  array<int, CaseResult>  $caseResults
     */
    public function __construct(
        public readonly string $suite,
        public readonly array $caseResults,
        public readonly float $durationMs,
    ) {}

    public function totalCases(): int
    {
        return count($this->caseResults);
    }

    public function passedCases(): int
    {
        $count = 0;

        foreach ($this->caseResults as $result) {
            if ($result->passed()) {
                $count++;
            }
        }

        return $count;
    }

    public function failedCases(): int
    {
        return $this->totalCases() - $this->passedCases();
    }

    public function allPassed(): bool
    {
        return $this->failedCases() === 0;
    }

    /**
     * @return array<int, CaseResult>
     */
    public function failures(): array
    {
        return array_values(
            array_filter(
                $this->caseResults,
                static fn (CaseResult $result): bool => ! $result->passed(),
            ),
        );
    }
}
