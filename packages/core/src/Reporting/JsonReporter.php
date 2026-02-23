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
                'score' => $caseResult->score(),
                'latency_ms' => $caseResult->latencyMs,
                'prompt_tokens' => $caseResult->promptTokens,
                'completion_tokens' => $caseResult->completionTokens,
                'total_tokens' => $caseResult->totalTokens(),
                'cost' => $caseResult->cost,
                'response' => [
                    'output' => $caseResult->output,
                    'tool_calls' => array_map(static fn ($toolCall): array => [
                        'name' => $toolCall->name,
                        'arguments' => $toolCall->arguments,
                    ], $caseResult->toolCalls),
                    'prompt_tokens' => $caseResult->promptTokens,
                    'completion_tokens' => $caseResult->completionTokens,
                    'cost' => $caseResult->cost,
                ],
                'assertions' => $assertions,
            ];
        }

        return [
            'suite' => $result->suite,
            'total_cases' => $result->totalCases(),
            'passed_cases' => $result->passedCases(),
            'failed_cases' => $result->failedCases(),
            'duration_ms' => $result->durationMs,
            'average_case_latency_ms' => $result->averageCaseLatencyMs(),
            'total_tokens' => $result->totalTokens(),
            'total_cost' => $result->totalCost(),
            'average_score' => $result->averageScore(),
            'cases' => $cases,
        ];
    }

    public function render(SuiteResult $result): string
    {
        $encoded = json_encode($this->toArray($result), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : '{}';
    }
}
