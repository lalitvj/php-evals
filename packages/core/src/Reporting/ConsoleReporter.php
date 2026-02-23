<?php

declare(strict_types=1);

namespace PhpEvals\Core\Reporting;

use PhpEvals\Core\Data\SuiteResult;

final class ConsoleReporter
{
    public function render(SuiteResult $result): string
    {
        $lines = [];
        $lines[] = sprintf('Suite: %s', $result->suite);
        $lines[] = sprintf(
            'Cases: %d | Passed: %d | Failed: %d | Duration: %.2fms | Avg latency: %.2fms | Tokens: %d | Cost: %.6f',
            $result->totalCases(),
            $result->passedCases(),
            $result->failedCases(),
            $result->durationMs,
            $result->averageCaseLatencyMs(),
            $result->totalTokens(),
            $result->totalCost(),
        );

        foreach ($result->failures() as $failure) {
            $lines[] = sprintf('- Case %s failed', $failure->caseId);
            foreach ($failure->assertionResults as $assertionResult) {
                if (! $assertionResult->passed && $assertionResult->message !== null) {
                    $lines[] = sprintf('  - [%s] %s', $assertionResult->type, $assertionResult->message);
                }
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
