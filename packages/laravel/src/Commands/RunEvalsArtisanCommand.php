<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Commands;

use Illuminate\Console\Command;
use PhpEvals\Core\Cli\PhpEvalsApplication;

final class RunEvalsArtisanCommand extends Command
{
    protected $signature = 'ai:eval
        {--suite= : Run a specific suite}
        {--model= : Override model name}
        {--format=table : Output format (table|json)}
        {--stop-on-failure : Stop on first failed case}';

    protected $description = 'Run AI evaluation suites.';

    public function handle(): int
    {
        $runner = $this->laravel->make(PhpEvalsApplication::class);
        $arguments = ['php-evals'];

        if (is_string($this->option('suite')) && $this->option('suite') !== '') {
            $arguments[] = '--suite='.$this->option('suite');
        }

        if (is_string($this->option('model')) && $this->option('model') !== '') {
            $arguments[] = '--model='.$this->option('model');
        }

        if (is_string($this->option('format')) && $this->option('format') !== '') {
            $arguments[] = '--format='.$this->option('format');
        }

        if ((bool) $this->option('stop-on-failure')) {
            $arguments[] = '--stop-on-failure';
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
}
