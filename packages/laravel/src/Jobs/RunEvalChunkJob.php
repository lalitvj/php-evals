<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PhpEvals\Core\Config\CoreConfig;
use PhpEvals\Core\Engine\CaseValidator;
use PhpEvals\Core\Engine\EvalRunner;
use PhpEvals\Core\Engine\RuntimeFactory;
use PhpEvals\Core\Engine\SuiteLoader;
use PhpEvals\Core\Reporting\JsonReporter;

final class RunEvalChunkJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly string $runId,
        private readonly string $suite,
        private readonly int $offset,
        private readonly int $limit,
        private readonly array $config,
    ) {}

    public function handle(): void
    {
        $coreConfig = CoreConfig::fromArray($this->config);
        $factory = new RuntimeFactory;
        $store = $factory->buildRunStore($coreConfig);

        $runner = new EvalRunner(
            $factory->buildModelClient($coreConfig),
            $factory->buildAssertionRegistry(
                $factory->buildSimilarityScorer($coreConfig),
                $factory->buildJudgeClient($coreConfig),
            ),
        );

        $loader = new SuiteLoader($coreConfig->datasetPath, new CaseValidator);
        $cases = array_slice($loader->loadSuite($this->suite), $this->offset, $this->limit);

        $result = $runner->runSuite(
            $this->suite,
            $cases,
            $coreConfig->model,
            false,
            $coreConfig->deterministic,
            $coreConfig->seed,
        );

        $chunkPayload = (new JsonReporter)->toArray($result);
        $chunkKey = sprintf('%s:%d:%d', $this->suite, $this->offset, $this->limit);

        $store->mutateRun($this->runId, function (array $run) use ($chunkKey, $chunkPayload): array {
            $queue = is_array($run['queue'] ?? null) ? $run['queue'] : [];
            $chunkResults = is_array($queue['chunk_results'] ?? null) ? $queue['chunk_results'] : [];
            $chunkResults[$chunkKey] = $chunkPayload;

            $completedChunks = is_array($queue['completed_chunks'] ?? null) ? $queue['completed_chunks'] : [];
            if (! in_array($chunkKey, $completedChunks, true)) {
                $completedChunks[] = $chunkKey;
                sort($completedChunks);
            }

            $queue['chunk_results'] = $chunkResults;
            $queue['completed_chunks'] = $completedChunks;

            [$suites, $summary] = $this->aggregateFromChunks($chunkResults);

            $totalChunks = (int) ($queue['total_chunks'] ?? count($completedChunks));
            $isComplete = $totalChunks > 0 && count($completedChunks) >= $totalChunks;

            $run['status'] = $isComplete ? 'completed' : 'running';
            $run['queue'] = $queue;
            $run['suites'] = $suites;
            $run['summary'] = $summary;
            $run['finished_at'] = $isComplete ? gmdate(DATE_ATOM) : null;

            return $run;
        });
    }

    /**
     * @param  array<string, array<string, mixed>>  $chunkResults
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>}
     */
    private function aggregateFromChunks(array $chunkResults): array
    {
        $bySuite = [];
        foreach ($chunkResults as $chunkPayload) {
            if (! is_array($chunkPayload)) {
                continue;
            }

            $suiteName = $chunkPayload['suite'] ?? null;
            if (! is_string($suiteName) || $suiteName === '') {
                continue;
            }

            if (! isset($bySuite[$suiteName])) {
                $bySuite[$suiteName] = [
                    'suite' => $suiteName,
                    'cases' => [],
                    'duration_ms' => 0.0,
                    'total_tokens' => 0,
                    'total_cost' => 0.0,
                ];
            }

            $bySuite[$suiteName]['duration_ms'] += (float) ($chunkPayload['duration_ms'] ?? 0.0);
            $bySuite[$suiteName]['total_tokens'] += (int) ($chunkPayload['total_tokens'] ?? 0);
            $bySuite[$suiteName]['total_cost'] += (float) ($chunkPayload['total_cost'] ?? 0.0);

            foreach (($chunkPayload['cases'] ?? []) as $case) {
                if (is_array($case)) {
                    $bySuite[$suiteName]['cases'][] = $case;
                }
            }
        }

        $suites = [];
        foreach ($bySuite as $suite) {
            $cases = $suite['cases'];
            $total = count($cases);
            $passed = 0;
            $scoreTotal = 0.0;
            $latencyTotal = 0.0;

            foreach ($cases as $case) {
                if ((bool) ($case['passed'] ?? false)) {
                    $passed++;
                }
                $scoreTotal += (float) ($case['score'] ?? 0.0);
                $latencyTotal += (float) ($case['latency_ms'] ?? 0.0);
            }

            $suite['total_cases'] = $total;
            $suite['passed_cases'] = $passed;
            $suite['failed_cases'] = $total - $passed;
            $suite['average_score'] = $total > 0 ? $scoreTotal / $total : 0.0;
            $suite['average_case_latency_ms'] = $total > 0 ? $latencyTotal / $total : 0.0;
            $suites[] = $suite;
        }

        $summary = [
            'total_cases' => 0,
            'passed_cases' => 0,
            'failed_cases' => 0,
            'duration_ms' => 0.0,
            'average_case_latency_ms' => 0.0,
            'total_tokens' => 0,
            'total_cost' => 0.0,
            'average_score' => 0.0,
        ];

        $scoreWeighted = 0.0;
        foreach ($suites as $suite) {
            $cases = (int) $suite['total_cases'];
            $summary['total_cases'] += $cases;
            $summary['passed_cases'] += (int) $suite['passed_cases'];
            $summary['failed_cases'] += (int) $suite['failed_cases'];
            $summary['duration_ms'] += (float) $suite['duration_ms'];
            $summary['total_tokens'] += (int) $suite['total_tokens'];
            $summary['total_cost'] += (float) $suite['total_cost'];
            $summary['average_case_latency_ms'] += (float) $suite['average_case_latency_ms'] * $cases;
            $scoreWeighted += (float) $suite['average_score'] * $cases;
        }

        if ($summary['total_cases'] > 0) {
            $summary['average_case_latency_ms'] /= $summary['total_cases'];
            $summary['average_score'] = $scoreWeighted / $summary['total_cases'];
        }

        return [$suites, $summary];
    }
}
