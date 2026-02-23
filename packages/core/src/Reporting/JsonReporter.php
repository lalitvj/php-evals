<?php

declare(strict_types=1);

namespace PhpEvals\Core\Reporting;

use PhpEvals\Core\Data\SuiteResult;

final class JsonReporter
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(SuiteResult $result): array
    {
        $cases = [];

        foreach ($result->caseResults as $caseResult) {
            $assertions = [];
            foreach ($caseResult->assertionResults as $assertionResult) {
                $assertions[] = [
                    'type' => $assertionResult->type,
                    'passed' => $assertionResult->passed,
                    'message' => $assertionResult->message,
                    'details' => $assertionResult->details,
                ];
            }

            $cases[] = [
                'id' => $caseResult->caseId,
                'passed' => $caseResult->passed(),
                'assertions' => $assertions,
            ];
        }

        return [
            'suite' => $result->suite,
            'total_cases' => $result->totalCases(),
            'passed_cases' => $result->passedCases(),
            'failed_cases' => $result->failedCases(),
            'duration_ms' => $result->durationMs,
            'cases' => $cases,
        ];
    }

    public function render(SuiteResult $result): string
    {
        $encoded = json_encode($this->toArray($result), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : '{}';
    }
}
