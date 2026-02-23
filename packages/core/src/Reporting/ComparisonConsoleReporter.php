<?php

declare(strict_types=1);

namespace PhpEvals\Core\Reporting;

final class ComparisonConsoleReporter
{
    /**
     * @param  array<string, mixed>  $comparison
     */
    public function render(array $comparison): string
    {
        $lines = [];
        $lines[] = sprintf('Comparison: baseline=%s candidate=%s', (string) ($comparison['baseline_run_id'] ?? 'n/a'), (string) ($comparison['candidate_run_id'] ?? 'n/a'));

        $metrics = is_array($comparison['metrics'] ?? null) ? $comparison['metrics'] : [];
        $lines[] = sprintf(
            'Regression metrics: pass_rate_drop=%.4f avg_score_drop=%.4f avg_latency_increase_ms=%.2f total_cost_increase=%.6f',
            (float) ($metrics['pass_rate_drop'] ?? 0.0),
            (float) ($metrics['avg_score_drop'] ?? 0.0),
            (float) ($metrics['avg_latency_increase_ms'] ?? 0.0),
            (float) ($metrics['total_cost_increase'] ?? 0.0),
        );

        $violations = is_array($comparison['violations'] ?? null) ? $comparison['violations'] : [];
        if ($violations === []) {
            $lines[] = 'Threshold check: PASS';
        } else {
            $lines[] = 'Threshold check: FAIL';
            foreach ($violations as $violation) {
                if (! is_array($violation)) {
                    continue;
                }

                $lines[] = sprintf(
                    '- %s actual=%.4f allowed=%.4f',
                    (string) ($violation['metric'] ?? 'metric'),
                    (float) ($violation['actual'] ?? 0.0),
                    (float) ($violation['allowed'] ?? 0.0),
                );
            }
        }

        $diffs = is_array($comparison['diffs'] ?? null) ? $comparison['diffs'] : [];
        $lines[] = sprintf('Per-case diffs: %d', count($diffs));
        foreach (array_slice($diffs, 0, 20) as $diff) {
            if (! is_array($diff)) {
                continue;
            }

            $caseId = (string) ($diff['case_id'] ?? 'unknown');
            $type = (string) ($diff['type'] ?? 'change');
            if ($type === 'case_change') {
                $lines[] = sprintf(
                    '- [%s] pass:%s->%s score:%.3f->%.3f',
                    $caseId,
                    ((bool) ($diff['baseline_passed'] ?? false)) ? 'pass' : 'fail',
                    ((bool) ($diff['candidate_passed'] ?? false)) ? 'pass' : 'fail',
                    (float) ($diff['baseline_score'] ?? 0.0),
                    (float) ($diff['candidate_score'] ?? 0.0),
                );

                $baselineOutput = is_string($diff['baseline_output'] ?? null) ? trim($diff['baseline_output']) : '';
                $candidateOutput = is_string($diff['candidate_output'] ?? null) ? trim($diff['candidate_output']) : '';
                if ($baselineOutput !== '' || $candidateOutput !== '') {
                    $lines[] = sprintf('  baseline: %s', $this->preview($baselineOutput));
                    $lines[] = sprintf('  candidate: %s', $this->preview($candidateOutput));
                }

                continue;
            }

            $lines[] = sprintf('- [%s] %s', $caseId, $type);
        }

        return implode(PHP_EOL, $lines);
    }

    private function preview(string $text, int $max = 120): string
    {
        if ($text === '') {
            return '(empty)';
        }

        if (strlen($text) <= $max) {
            return $text;
        }

        return substr($text, 0, $max - 3).'...';
    }
}
