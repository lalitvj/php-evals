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
        {--config= : Config file path}';

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
