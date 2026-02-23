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
        public readonly string $output = '',
        /**
         * @var array<int, ToolCall>
         */
        public readonly array $toolCalls = [],
        public readonly float $latencyMs = 0.0,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly float $cost = 0.0,
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

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }

    public function score(): float
    {
        $scoreTotal = 0.0;
        $scoreCount = 0;

        foreach ($this->assertionResults as $assertionResult) {
            $detailScore = $assertionResult->details['score'] ?? null;
            if (is_int($detailScore) || is_float($detailScore)) {
                $scoreTotal += max(0.0, min(1.0, (float) $detailScore));
                $scoreCount++;

                continue;
            }

            $scoreTotal += $assertionResult->passed ? 1.0 : 0.0;
            $scoreCount++;
        }

        if ($scoreCount === 0) {
            return $this->passed() ? 1.0 : 0.0;
        }

        return $scoreTotal / $scoreCount;
    }
}
