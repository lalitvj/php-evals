<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Commands;

use Illuminate\Console\Command;
use PhpEvals\Core\Config\CoreConfig;
use PhpEvals\Core\Engine\RunComparator;
use PhpEvals\Core\Engine\RuntimeFactory;
use PhpEvals\Core\Reporting\ComparisonConsoleReporter;

final class CompareEvalsArtisanCommand extends Command
{
    protected $signature = 'ai:eval:compare
        {baseline_run_id : Baseline run id}
        {candidate_run_id? : Candidate run id (optional, defaults to latest stored run)}
        {--format=table : Output format (table|json)}
        {--fail-threshold= : Threshold map, e.g. pass_rate_drop:0.02,avg_score_drop:0.05}';

    protected $description = 'Compare baseline and candidate eval runs with regression thresholds.';

    public function handle(): int
    {
        $baseline = $this->argument('baseline_run_id');
        if (! is_string($baseline) || $baseline === '') {
            $this->error('baseline_run_id must be a non-empty string.');

            return 2;
        }

        $candidate = $this->argument('candidate_run_id');
        $format = $this->option('format');
        if (! is_string($format) || $format === '') {
            $format = 'table';
        }

        if (! in_array($format, ['table', 'json'], true)) {
            $this->error(sprintf('Invalid format "%s". Expected one of: table, json.', $format));

            return 2;
        }

        $config = config('ai-evals', []);
        $coreConfig = CoreConfig::fromArray(is_array($config) ? $config : []);
        $store = (new RuntimeFactory)->buildRunStore($coreConfig);

        $baselineRun = $store->getRun($baseline);
        if (! is_array($baselineRun)) {
            $this->error(sprintf('Baseline run %s not found.', $baseline));

            return 2;
        }

        $candidateRunId = is_string($candidate) && $candidate !== ''
            ? $candidate
            : $this->resolveLatestCandidateRunId($store->listRuns(), $baseline);

        if ($candidateRunId === null) {
            $this->error('No candidate run provided and no alternate stored run was found.');

            return 2;
        }

        $candidateRun = $store->getRun($candidateRunId);
        if (! is_array($candidateRun)) {
            $this->error(sprintf('Candidate run %s not found.', $candidateRunId));

            return 2;
        }

        $comparison = (new RunComparator)->compare(
            $baselineRun,
            $candidateRun,
            $this->resolveThresholds($coreConfig->failThresholds, $this->option('fail-threshold')),
        );

        if ($format === 'json') {
            $this->line((string) json_encode($comparison, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->line((new ComparisonConsoleReporter)->render($comparison));
        }

        return (bool) ($comparison['passed'] ?? false) ? 0 : 1;
    }

    /**
     * @param  array<int, array<string, mixed>>  $runs
     */
    private function resolveLatestCandidateRunId(array $runs, string $baselineRunId): ?string
    {
        foreach ($runs as $run) {
            $id = $run['id'] ?? null;
            if (is_string($id) && $id !== '' && $id !== $baselineRunId) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @param  array<string, float>  $defaults
     * @return array<string, float>
     */
    private function resolveThresholds(array $defaults, mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return $defaults;
        }

        $thresholds = [];
        foreach (explode(',', $value) as $entry) {
            $trimmed = trim($entry);
            if ($trimmed === '' || ! str_contains($trimmed, ':')) {
                continue;
            }

            [$metric, $threshold] = explode(':', $trimmed, 2);
            $metric = trim($metric);
            $threshold = trim($threshold);

            if ($metric === '' || ! is_numeric($threshold)) {
                continue;
            }

            $thresholds[$metric] = (float) $threshold;
        }

        return $thresholds;
    }
}
