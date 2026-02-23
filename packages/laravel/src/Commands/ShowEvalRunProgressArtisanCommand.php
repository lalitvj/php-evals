<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Commands;

use Illuminate\Console\Command;
use PhpEvals\Core\Config\CoreConfig;
use PhpEvals\Core\Engine\RuntimeFactory;

final class ShowEvalRunProgressArtisanCommand extends Command
{
    protected $signature = 'ai:eval:progress {run_id : Run id to inspect}';

    protected $description = 'Show queued run progress and resumability information.';

    public function handle(): int
    {
        $config = config('ai-evals', []);
        $coreConfig = CoreConfig::fromArray(is_array($config) ? $config : []);
        $store = (new RuntimeFactory)->buildRunStore($coreConfig);

        $runIdArgument = $this->argument('run_id');
        if (! is_string($runIdArgument) || $runIdArgument === '') {
            $this->error('run_id must be a non-empty string.');

            return 2;
        }

        $runId = $runIdArgument;
        $run = $store->getRun($runId);
        if (! is_array($run)) {
            $this->error(sprintf('Run %s not found.', $runId));

            return 2;
        }

        $total = (int) ($run['queue']['total_chunks'] ?? 0);
        $completed = count(is_array($run['queue']['completed_chunks'] ?? null) ? $run['queue']['completed_chunks'] : []);

        $this->line(sprintf('Run: %s', $runId));
        $this->line(sprintf('Status: %s', (string) ($run['status'] ?? 'unknown')));
        $this->line(sprintf('Chunks: %d/%d', $completed, $total));

        if ($completed < $total) {
            $this->line(sprintf('Resume command: php artisan ai:eval:queue --resume-run-id=%s', $runId));
        }

        return 0;
    }
}
