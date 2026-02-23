<?php

declare(strict_types=1);

namespace PhpEvals\Core\Cli;

use PhpEvals\Core\Config\CoreConfig;
use PhpEvals\Core\Engine\CaseValidator;
use PhpEvals\Core\Engine\EvalRunner;
use PhpEvals\Core\Engine\RuntimeFactory;
use PhpEvals\Core\Engine\SuiteLoader;
use PhpEvals\Core\Exceptions\EvalExecutionException;
use PhpEvals\Core\Exceptions\InvalidEvalCaseException;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;
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
            $runner = new EvalRunner(
                $factory->buildModelClient($config),
                $factory->buildAssertionRegistry($factory->buildSimilarityScorer($config)),
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

            foreach ($suites as $suite) {
                $result = $runner->runSuite(
                    $suite,
                    $loader->loadSuite($suite),
                    $config->model,
                    $config->stopOnFailure,
                );

                $allPassed = $allPassed && $result->allPassed();
                $jsonPayload[] = $jsonReporter->toArray($result);

                if ($config->format === 'json') {
                    continue;
                }

                $writer($consoleReporter->render($result));
                $writer('');
            }

            if ($config->format === 'json') {
                $writer((string) json_encode(['suites' => $jsonPayload], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            if ($config->jsonReportPath !== null) {
                $encoded = json_encode(['suites' => $jsonPayload], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                if ($encoded !== false) {
                    file_put_contents($config->jsonReportPath, $encoded);
                }
            }

            return $allPassed ? 0 : 1;
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

        foreach (array_slice($argv, 1) as $argument) {
            if ($argument === '--stop-on-failure') {
                $options['stop_on_failure'] = true;

                continue;
            }

            if ($argument === '--help' || $argument === '-h') {
                $options['help'] = true;

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

        if (is_string($options['suite'] ?? null)) {
            $merged['suite'] = $options['suite'];
        }

        if (is_string($options['model'] ?? null)) {
            $merged['model'] = $options['model'];
        }

        if (is_string($options['format'] ?? null)) {
            $merged['format'] = $options['format'];
        }

        if (($options['stop_on_failure'] ?? false) === true) {
            $merged['stop_on_failure'] = true;
        }

        if (is_string($options['json_report_path'] ?? null)) {
            $merged['json_report_path'] = $options['json_report_path'];
        }

        if (is_string($options['dataset_path'] ?? null)) {
            $merged['dataset_path'] = $options['dataset_path'];
        }

        return CoreConfig::fromArray($merged);
    }

    private function helpText(): string
    {
        return implode(PHP_EOL, [
            'php-evals',
            '',
            'Options:',
            '  --suite=<name>            Run a specific suite.',
            '  --model=<model>           Override model name.',
            '  --format=table|json       Output format.',
            '  --stop-on-failure         Stop suite execution on first failed case.',
            '  --dataset-path=<path>     Override dataset directory.',
            '  --json-report-path=<path> Write JSON report to file.',
            '  --config=<path>           Load configuration file (default ./php-evals.php).',
            '  --help                    Print this help.',
        ]);
    }
}
