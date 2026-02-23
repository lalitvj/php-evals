<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Commands;

use Illuminate\Console\Command;
use PhpEvals\Core\Cli\PhpEvalsApplication;

final class RunEvalsArtisanCommand extends Command
{
    protected $signature = 'ai:eval
        {suite? : Optional suite argument (same as --suite)}
        {--suite= : Run a specific suite}
        {--model= : Override model name}
        {--format=table : Output format (table|json)}
        {--stop-on-failure : Stop on first failed case}
        {--dataset-path= : Override dataset directory}
        {--json-report-path= : Write JSON report to file}
        {--config= : Config file path}
        {--store-runs : Persist run output to run store}
        {--run-id= : Explicit run id}
        {--run-store-driver= : file|database}
        {--run-store-path= : File run store path}
        {--run-store-dsn= : PDO DSN for DB run store}
        {--run-store-user= : DB username for run store}
        {--run-store-password= : DB password for run store}
        {--deterministic : Deterministic mode}
        {--seed= : Deterministic seed}
        {--cache-enabled : Enable provider response cache}
        {--cache-path= : Cache file path}
        {--replay-run-id= : Replay responses from previous run}
        {--compare-baseline-run-id= : Baseline run id}
        {--compare-candidate-run-id= : Candidate run id}
        {--fail-threshold= : Threshold map (metric:value,...)}';

    protected $description = 'Run AI evaluation suites.';

    public function handle(): int
    {
        $runner = $this->laravel->make(PhpEvalsApplication::class);
        $arguments = ['php-evals'];
        $suite = $this->resolveSuite();
        $format = $this->resolveFormat();

        if ($format === null) {
            return 2;
        }

        if ($suite !== null) {
            $arguments[] = '--suite='.$suite;
        }

        if (is_string($this->option('model')) && $this->option('model') !== '') {
            $arguments[] = '--model='.$this->option('model');
        }

        if ($format !== '') {
            $arguments[] = '--format='.$format;
        }

        if ((bool) $this->option('stop-on-failure')) {
            $arguments[] = '--stop-on-failure';
        }

        foreach (['dataset-path', 'json-report-path', 'config'] as $option) {
            $value = $this->option($option);
            if (is_string($value) && $value !== '') {
                $arguments[] = sprintf('--%s=%s', $option, $value);
            }
        }

        foreach ([
            'run-id',
            'run-store-driver',
            'run-store-path',
            'run-store-dsn',
            'run-store-user',
            'run-store-password',
            'seed',
            'cache-path',
            'replay-run-id',
            'compare-baseline-run-id',
            'compare-candidate-run-id',
            'fail-threshold',
        ] as $option) {
            $value = $this->option($option);
            if (is_string($value) && $value !== '') {
                $arguments[] = sprintf('--%s=%s', $option, $value);
            }
        }

        foreach (['store-runs', 'deterministic', 'cache-enabled'] as $option) {
            if ((bool) $this->option($option)) {
                $arguments[] = '--'.$option;
            }
        }

        return $runner->run(
            $arguments,
            function (string $line): void {
                if ($line !== '') {
                    $this->line($line);
                }
            },
        );
    }

    private function resolveSuite(): ?string
    {
        $suiteArgument = $this->argument('suite');
        if (is_string($suiteArgument) && $suiteArgument !== '') {
            return $suiteArgument;
        }

        $suiteOption = $this->option('suite');
        if (is_string($suiteOption) && $suiteOption !== '') {
            return $suiteOption;
        }

        return null;
    }

    private function resolveFormat(): ?string
    {
        $format = $this->option('format');
        if (! is_string($format) || $format === '') {
            return 'table';
        }

        if (! in_array($format, ['table', 'json'], true)) {
            $this->error(sprintf('Invalid format "%s". Expected one of: table, json.', $format));

            return null;
        }

        return $format;
    }
}
