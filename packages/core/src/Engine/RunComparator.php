<?php

declare(strict_types=1);

namespace PhpEvals\Core\Engine;

final class RunComparator
{
    /**
     * @param  array<string, mixed>  $baselineRun
     * @param  array<string, mixed>  $candidateRun
     * @param  array<string, float>  $thresholds
     * @return array<string, mixed>
     */
    public function compare(array $baselineRun, array $candidateRun, array $thresholds = []): array
    {
        $baselineSummary = is_array($baselineRun['summary'] ?? null) ? $baselineRun['summary'] : [];
        $candidateSummary = is_array($candidateRun['summary'] ?? null) ? $candidateRun['summary'] : [];

        $baselineTotal = max(1, (int) ($baselineSummary['total_cases'] ?? 0));
        $candidateTotal = max(1, (int) ($candidateSummary['total_cases'] ?? 0));

        $baselinePassRate = ((int) ($baselineSummary['passed_cases'] ?? 0)) / $baselineTotal;
        $candidatePassRate = ((int) ($candidateSummary['passed_cases'] ?? 0)) / $candidateTotal;
        $baselineAvgScore = (float) ($baselineSummary['average_score'] ?? 0.0);
        $candidateAvgScore = (float) ($candidateSummary['average_score'] ?? 0.0);
        $baselineAvgLatency = (float) ($baselineSummary['average_case_latency_ms'] ?? 0.0);
        $candidateAvgLatency = (float) ($candidateSummary['average_case_latency_ms'] ?? 0.0);
        $baselineCost = (float) ($baselineSummary['total_cost'] ?? 0.0);
        $candidateCost = (float) ($candidateSummary['total_cost'] ?? 0.0);

        $metrics = [
            'pass_rate_drop' => max(0.0, $baselinePassRate - $candidatePassRate),
            'avg_score_drop' => max(0.0, $baselineAvgScore - $candidateAvgScore),
            'avg_latency_increase_ms' => max(0.0, $candidateAvgLatency - $baselineAvgLatency),
            'total_cost_increase' => max(0.0, $candidateCost - $baselineCost),
        ];

        $violations = [];
        foreach ($thresholds as $metric => $allowed) {
            $actual = $metrics[$metric] ?? null;
            if (! is_float($actual) && ! is_int($actual)) {
                continue;
            }

            if ((float) $actual > $allowed) {
                $violations[] = [
                    'metric' => $metric,
                    'allowed' => $allowed,
                    'actual' => (float) $actual,
                ];
            }
        }

        $baselineCases = $this->indexCases($baselineRun);
        $candidateCases = $this->indexCases($candidateRun);

        $diffs = [];
        $allCaseIds = array_unique(array_merge(array_keys($baselineCases), array_keys($candidateCases)));
        sort($allCaseIds);

        foreach ($allCaseIds as $caseId) {
            $baselineCase = $baselineCases[$caseId] ?? null;
            $candidateCase = $candidateCases[$caseId] ?? null;
            if (! is_array($baselineCase) || ! is_array($candidateCase)) {
                $diffs[] = [
                    'case_id' => $caseId,
                    'type' => 'missing_case',
                    'baseline_exists' => is_array($baselineCase),
                    'candidate_exists' => is_array($candidateCase),
                ];

                continue;
            }

            $baselinePassed = (bool) ($baselineCase['passed'] ?? false);
            $candidatePassed = (bool) ($candidateCase['passed'] ?? false);
            $baselineScore = (float) ($baselineCase['score'] ?? 0.0);
            $candidateScore = (float) ($candidateCase['score'] ?? 0.0);

            if ($baselinePassed !== $candidatePassed || abs($baselineScore - $candidateScore) >= 0.15) {
                $diffs[] = [
                    'case_id' => $caseId,
                    'type' => 'case_change',
                    'baseline_passed' => $baselinePassed,
                    'candidate_passed' => $candidatePassed,
                    'baseline_score' => $baselineScore,
                    'candidate_score' => $candidateScore,
                    'baseline_output' => $baselineCase['response']['output'] ?? null,
                    'candidate_output' => $candidateCase['response']['output'] ?? null,
                ];
            }
        }

        return [
            'baseline_run_id' => $baselineRun['id'] ?? null,
            'candidate_run_id' => $candidateRun['id'] ?? null,
            'metrics' => $metrics,
            'violations' => $violations,
            'diffs' => $diffs,
            'passed' => $violations === [],
        ];
    }

    /**
     * @param  array<string, mixed>  $run
     * @return array<string, array<string, mixed>>
     */
    private function indexCases(array $run): array
    {
        $index = [];
        foreach (($run['suites'] ?? []) as $suite) {
            if (! is_array($suite)) {
                continue;
            }

            foreach (($suite['cases'] ?? []) as $case) {
                if (! is_array($case)) {
                    continue;
                }

                $caseId = $case['id'] ?? null;
                if (is_string($caseId) && $caseId !== '') {
                    $index[$caseId] = $case;
                }
            }
        }

        return $index;
    }
}
