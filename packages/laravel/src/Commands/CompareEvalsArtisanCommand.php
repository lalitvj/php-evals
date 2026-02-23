<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Commands;

use Illuminate\Console\Command;
use PhpEvals\Core\Cli\PhpEvalsApplication;

final class CompareEvalsArtisanCommand extends Command
{
    protected $signature = 'ai:eval:compare
        {baseline_run_id : Baseline run id}
        {candidate_run_id? : Candidate run id (optional, defaults to latest/current)}
        {--format=table : Output format (table|json)}
        {--fail-threshold= : Threshold map, e.g. pass_rate_drop:0.02,avg_score_drop:0.05}';

    protected $description = 'Compare baseline and candidate eval runs with regression thresholds.';

    public function handle(): int
    {
        $runner = $this->laravel->make(PhpEvalsApplication::class);
        $baseline = $this->argument('baseline_run_id');
        if (! is_string($baseline) || $baseline === '') {
            $this->error('baseline_run_id must be a non-empty string.');

            return 2;
        }

        $arguments = [
            'php-evals',
            '--compare-baseline-run-id='.$baseline,
            '--store-runs',
        ];

        $candidate = $this->argument('candidate_run_id');
        if (is_string($candidate) && $candidate !== '') {
            $arguments[] = '--compare-candidate-run-id='.$candidate;
        }

        $format = $this->option('format');
        if (is_string($format) && $format !== '') {
            $arguments[] = '--format='.$format;
        }

        $threshold = $this->option('fail-threshold');
        if (is_string($threshold) && $threshold !== '') {
            $arguments[] = '--fail-threshold='.$threshold;
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
