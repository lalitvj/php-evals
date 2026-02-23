<?php

declare(strict_types=1);

namespace PhpEvals\Core\Cli;

use PhpEvals\Core\Config\CoreConfig;
use PhpEvals\Core\Contracts\RunStore;
use PhpEvals\Core\Engine\CaseValidator;
use PhpEvals\Core\Engine\EvalRunner;
use PhpEvals\Core\Engine\RunComparator;
use PhpEvals\Core\Engine\RuntimeFactory;
use PhpEvals\Core\Engine\SuiteLoader;
use PhpEvals\Core\Exceptions\EvalExecutionException;
use PhpEvals\Core\Exceptions\InvalidEvalCaseException;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;
use PhpEvals\Core\Reporting\ComparisonConsoleReporter;
use PhpEvals\Core\Reporting\ConsoleReporter;
use PhpEvals\Core\Reporting\JsonReporter;

final class PhpEvalsApplication
{
    /**
     * @param  array<string, mixed>  $baseConfig
     */
    public function __construct(
        private readonly array $baseConfig = [],
        private readonly ?string $workingDirectory = null,
    ) {}

    /**
     * @param  array<int, string>  $argv
     */
    public function run(array $argv, ?callable $writer = null): int
    {
        $writer ??= static function (string $line): void {
            echo $line.PHP_EOL;
        };

        try {
            $options = $this->parseOptions($argv);

            if (($options['help'] ?? false) === true) {
                $writer($this->helpText());

                return 0;
            }

            $config = $this->buildConfig($options);
            $factory = new RuntimeFactory;
            $runStore = $this->resolveRunStore($factory, $config);

            $runner = new EvalRunner(
                $factory->buildModelClient($config),
                $factory->buildAssertionRegistry(
                    $factory->buildSimilarityScorer($config),
                    $factory->buildJudgeClient($config),
                ),
            );

            $loader = new SuiteLoader($config->datasetPath, new CaseValidator);
            $suites = isset($options['suite']) ? [(string) $options['suite']] : $loader->listSuites();

            if ($suites === []) {
                throw new InvalidEvalCaseException(sprintf('No suites found in %s.', $config->datasetPath));
            }

            $consoleReporter = new ConsoleReporter;
            $jsonReporter = new JsonReporter;
            $allPassed = true;
            $jsonPayload = [];

            $runId = null;
            if ($config->storeRuns && $runStore !== null) {
                $runId = $runStore->createRun([
                    'status' => 'running',
                    'metadata' => [
                        'mode' => 'eval',
                        'model' => $config->model,
                        'dataset_path' => $config->datasetPath,
                        'deterministic' => $config->deterministic,
                        'seed' => $config->seed,
                    ],
                    'started_at' => gmdate(DATE_ATOM),
                ], $config->runId);
            }

            foreach ($suites as $suite) {
                $result = $runner->runSuite(
                    $suite,
                    $loader->loadSuite($suite),
                    $config->model,
                    $config->stopOnFailure,
                    $config->deterministic,
                    $config->seed,
                );

                $allPassed = $allPassed && $result->allPassed();
                $jsonPayload[] = $jsonReporter->toArray($result);

                if ($config->format === 'json') {
                    continue;
                }

                $writer($consoleReporter->render($result));
                $writer('');
            }

            $summary = $this->summarizeSuites($jsonPayload);
            if ($runId !== null && $runStore !== null) {
                $runStore->updateRun($runId, [
                    'status' => 'completed',
                    'finished_at' => gmdate(DATE_ATOM),
                    'summary' => $summary,
                    'suites' => $jsonPayload,
                ]);
            }

            $comparison = null;
            if ($config->compareBaselineRunId !== null) {
                if ($runStore === null) {
                    throw new RuntimeConfigurationException('Comparison requires a configured run store.');
                }

                $baselineRun = $runStore->getRun($config->compareBaselineRunId);
                if (! is_array($baselineRun)) {
                    throw new RuntimeConfigurationException(sprintf('Baseline run "%s" not found.', $config->compareBaselineRunId));
                }

                $candidateRun = null;
                if ($config->compareCandidateRunId !== null) {
                    $candidateRun = $runStore->getRun($config->compareCandidateRunId);
                    if (! is_array($candidateRun)) {
                        throw new RuntimeConfigurationException(sprintf('Candidate run "%s" not found.', $config->compareCandidateRunId));
                    }
                }

                if (! is_array($candidateRun)) {
                    $candidateRun = [
                        'id' => $runId ?? 'current',
                        'summary' => $summary,
                        'suites' => $jsonPayload,
                    ];
                }

                $comparison = (new RunComparator)->compare($baselineRun, $candidateRun, $config->failThresholds);
                if ($config->format !== 'json') {
                    $writer((new ComparisonConsoleReporter)->render($comparison));
                }
            }

            $outputPayload = [
                'run_id' => $runId,
                'summary' => $summary,
                'suites' => $jsonPayload,
            ];

            if (is_array($comparison)) {
                $outputPayload['comparison'] = $comparison;
            }

            if ($config->format === 'json') {
                $writer((string) json_encode($outputPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            if ($config->jsonReportPath !== null) {
                $encoded = json_encode($outputPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                if ($encoded !== false) {
                    file_put_contents($config->jsonReportPath, $encoded);
                }
            }

            $comparisonPassed = ! is_array($comparison) || (bool) ($comparison['passed'] ?? false);

            return ($allPassed && $comparisonPassed) ? 0 : 1;
        } catch (InvalidEvalCaseException|RuntimeConfigurationException|EvalExecutionException $exception) {
            $writer('ERROR: '.$exception->getMessage());

            return 2;
        }
    }

    /**
     * @param  array<int, string>  $argv
     * @return array<string, mixed>
     */
    private function parseOptions(array $argv): array
    {
        $options = [];
        $booleanFlags = [
            '--stop-on-failure' => 'stop_on_failure',
            '--help' => 'help',
            '-h' => 'help',
            '--deterministic' => 'deterministic',
            '--cache-enabled' => 'cache_enabled',
            '--store-runs' => 'store_runs',
        ];

        foreach (array_slice($argv, 1) as $argument) {
            if (isset($booleanFlags[$argument])) {
                $options[$booleanFlags[$argument]] = true;

                continue;
            }

            if (! str_starts_with($argument, '--') || ! str_contains($argument, '=')) {
                continue;
            }

            [$key, $value] = explode('=', substr($argument, 2), 2);
            $options[str_replace('-', '_', $key)] = $value;
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function buildConfig(array $options): CoreConfig
    {
        $workingDirectory = $this->workingDirectory ?? getcwd();
        $configPath = is_string($options['config'] ?? null)
            ? $options['config']
            : $workingDirectory.DIRECTORY_SEPARATOR.'php-evals.php';

        $fileConfig = [];
        if (is_file($configPath)) {
            $loaded = require $configPath;
            if (is_array($loaded)) {
                $fileConfig = $loaded;
            }
        }

        $merged = array_merge($this->baseConfig, $fileConfig);

        foreach ([
            'suite',
            'model',
            'format',
            'json_report_path',
            'dataset_path',
            'run_id',
            'run_store_driver',
            'run_store_path',
            'run_store_dsn',
            'run_store_user',
            'run_store_password',
            'cache_path',
            'replay_run_id',
            'compare_baseline_run_id',
            'compare_candidate_run_id',
            'config',
        ] as $option) {
            if (is_string($options[$option] ?? null)) {
                $merged[$option] = $options[$option];
            }
        }

        foreach (['stop_on_failure', 'deterministic', 'cache_enabled', 'store_runs'] as $option) {
            if (($options[$option] ?? false) === true) {
                $merged[$option] = true;
            }
        }

        if (is_string($options['seed'] ?? null) && is_numeric($options['seed'])) {
            $merged['seed'] = (int) $options['seed'];
        }

        if (is_string($options['cache_ttl'] ?? null) && is_numeric($options['cache_ttl'])) {
            $merged['cache_ttl'] = (int) $options['cache_ttl'];
        }

        if (is_string($options['fail_threshold'] ?? null) && $options['fail_threshold'] !== '') {
            $merged['fail_thresholds'] = $this->parseThresholds($options['fail_threshold']);
        }

        return CoreConfig::fromArray($merged);
    }

    /**
     * @param  array<int, array<string, mixed>>  $suites
     * @return array<string, mixed>
     */
    private function summarizeSuites(array $suites): array
    {
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
            $cases = (int) ($suite['total_cases'] ?? 0);
            $summary['total_cases'] += $cases;
            $summary['passed_cases'] += (int) ($suite['passed_cases'] ?? 0);
            $summary['failed_cases'] += (int) ($suite['failed_cases'] ?? 0);
            $summary['duration_ms'] += (float) ($suite['duration_ms'] ?? 0.0);
            $summary['total_tokens'] += (int) ($suite['total_tokens'] ?? 0);
            $summary['total_cost'] += (float) ($suite['total_cost'] ?? 0.0);
            $summary['average_case_latency_ms'] += (float) ($suite['average_case_latency_ms'] ?? 0.0) * $cases;
            $scoreWeighted += (float) ($suite['average_score'] ?? 0.0) * $cases;
        }

        if ($summary['total_cases'] > 0) {
            $summary['average_case_latency_ms'] /= $summary['total_cases'];
            $summary['average_score'] = $scoreWeighted / $summary['total_cases'];
        }

        return $summary;
    }

    /**
     * @return array<string, float>
     */
    private function parseThresholds(string $input): array
    {
        $thresholds = [];
        foreach (explode(',', $input) as $entry) {
            $trimmed = trim($entry);
            if ($trimmed === '' || ! str_contains($trimmed, ':')) {
                continue;
            }

            [$metric, $value] = explode(':', $trimmed, 2);
            $metric = trim($metric);
            if ($metric === '' || ! is_numeric(trim($value))) {
                continue;
            }

            $thresholds[$metric] = (float) trim($value);
        }

        return $thresholds;
    }

    private function resolveRunStore(RuntimeFactory $factory, CoreConfig $config): ?RunStore
    {
        if (
            ! $config->storeRuns
            && $config->replayRunId === null
            && $config->compareBaselineRunId === null
            && $config->compareCandidateRunId === null
        ) {
            return null;
        }

        return $factory->buildRunStore($config);
    }

    private function helpText(): string
    {
        return implode(PHP_EOL, [
            'php-evals',
            '',
            'Options:',
            '  --suite=<name>                  Run a specific suite.',
            '  --model=<model>                 Override model name.',
            '  --format=table|json             Output format.',
            '  --stop-on-failure               Stop suite execution on first failed case.',
            '  --dataset-path=<path>           Override dataset directory.',
            '  --json-report-path=<path>       Write JSON report to file.',
            '  --config=<path>                 Load configuration file (default ./php-evals.php).',
            '  --store-runs                    Persist run output into run store.',
            '  --run-id=<id>                   Use a specific run id when storing output.',
            '  --run-store-driver=file|database',
            '  --run-store-path=<path>',
            '  --run-store-dsn=<dsn>',
            '  --run-store-user=<user>',
            '  --run-store-password=<password>',
            '  --deterministic                 Enable deterministic request mode.',
            '  --seed=<int>                    Seed value for deterministic requests.',
            '  --cache-enabled                 Enable provider response cache.',
            '  --cache-path=<path>             Path for provider cache files.',
            '  --cache-ttl=<seconds>           Cache entry time-to-live in seconds.',
            '  --replay-run-id=<run_id>        Replay responses from a stored run.',
            '  --compare-baseline-run-id=<id>  Compare current/candidate run against baseline run.',
            '  --compare-candidate-run-id=<id> Compare two stored runs.',
            '  --fail-threshold=<k:v,...>      Regression thresholds (pass_rate_drop:0.02,avg_score_drop:0.05).',
            '  --help                          Print this help.',
        ]);
    }
}
