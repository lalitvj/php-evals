<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Commands;

use Illuminate\Console\Command;
use PhpEvals\Core\Config\CoreConfig;
use PhpEvals\Core\Engine\CaseValidator;
use PhpEvals\Core\Engine\RuntimeFactory;
use PhpEvals\Core\Engine\SuiteLoader;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;
use PhpEvals\Laravel\Jobs\RunEvalChunkJob;

final class QueueEvalsArtisanCommand extends Command
{
    protected $signature = 'ai:eval:queue
        {--suite= : Optional suite name}
        {--chunk-size=25 : Number of cases per job}
        {--resume-run-id= : Resume a previously queued run}
        {--run-id= : Explicit run id for new queued run}
        {--model= : Override model}
        {--deterministic : Enable deterministic mode}
        {--seed= : Seed value}
        {--connection= : Queue connection}
        {--queue= : Queue name}';

    protected $description = 'Dispatch evaluation suites in chunked queue jobs with resumable progress.';

    public function handle(): int
    {
        $baseConfig = $this->baseConfig();
        if (is_string($this->option('model')) && $this->option('model') !== '') {
            $baseConfig['model'] = $this->option('model');
        }

        if ((bool) $this->option('deterministic')) {
            $baseConfig['deterministic'] = true;
        }

        if (is_string($this->option('seed')) && is_numeric($this->option('seed'))) {
            $baseConfig['seed'] = (int) $this->option('seed');
        }

        $chunkSize = (int) $this->option('chunk-size');
        if ($chunkSize <= 0) {
            $this->error('chunk-size must be > 0.');

            return 2;
        }

        $config = CoreConfig::fromArray($baseConfig);
        $factory = new RuntimeFactory;
        $store = $factory->buildRunStore($config);

        $resumeRunId = $this->option('resume-run-id');
        if (is_string($resumeRunId) && $resumeRunId !== '') {
            $run = $store->getRun($resumeRunId);
            if (! is_array($run)) {
                $this->error(sprintf('Run %s not found for resume.', $resumeRunId));

                return 2;
            }

            $this->dispatchMissingChunks($resumeRunId, $run, $baseConfig);
            $this->info(sprintf('Resumed run %s', $resumeRunId));
            $this->line($this->progressLine($run));

            return 0;
        }

        $loader = new SuiteLoader($config->datasetPath, new CaseValidator);
        $suiteOption = $this->option('suite');
        $suites = is_string($suiteOption) && $suiteOption !== '' ? [$suiteOption] : $loader->listSuites();
        if ($suites === []) {
            throw new RuntimeConfigurationException(sprintf('No suites found in %s.', $config->datasetPath));
        }

        $manifest = [];
        $totalChunks = 0;
        foreach ($suites as $suite) {
            $cases = $loader->loadSuite($suite);
            $count = count($cases);
            $chunks = [];
            for ($offset = 0; $offset < $count; $offset += $chunkSize) {
                $limit = min($chunkSize, $count - $offset);
                $key = sprintf('%s:%d:%d', $suite, $offset, $limit);
                $chunks[] = [
                    'key' => $key,
                    'suite' => $suite,
                    'offset' => $offset,
                    'limit' => $limit,
                ];
                $totalChunks++;
            }

            $manifest[$suite] = [
                'total_cases' => $count,
                'chunks' => $chunks,
            ];
        }

        $runIdOption = $this->option('run-id');
        $runId = $store->createRun([
            'status' => 'queued',
            'started_at' => gmdate(DATE_ATOM),
            'queue' => [
                'chunk_size' => $chunkSize,
                'total_chunks' => $totalChunks,
                'completed_chunks' => [],
                'manifest' => $manifest,
                'chunk_results' => [],
            ],
            'metadata' => [
                'mode' => 'queue',
                'model' => $config->model,
                'dataset_path' => $config->datasetPath,
            ],
        ], is_string($runIdOption) && $runIdOption !== '' ? $runIdOption : null);

        $run = $store->getRun($runId);
        if (! is_array($run)) {
            $this->error('Failed to initialize queued run metadata.');

            return 2;
        }

        $this->dispatchMissingChunks($runId, $run, $baseConfig);

        $this->info(sprintf('Queued run %s', $runId));
        $this->line($this->progressLine($run));

        return 0;
    }

    /**
     * @param  array<string, mixed>  $run
     * @param  array<string, mixed>  $baseConfig
     */
    private function dispatchMissingChunks(string $runId, array $run, array $baseConfig): void
    {
        $manifest = is_array($run['queue']['manifest'] ?? null) ? $run['queue']['manifest'] : [];
        $completed = is_array($run['queue']['completed_chunks'] ?? null) ? $run['queue']['completed_chunks'] : [];
        $completedMap = [];
        foreach ($completed as $chunkKey) {
            if (is_string($chunkKey)) {
                $completedMap[$chunkKey] = true;
            }
        }

        $connection = is_string($this->option('connection')) && $this->option('connection') !== ''
            ? $this->option('connection')
            : null;
        $queue = is_string($this->option('queue')) && $this->option('queue') !== ''
            ? $this->option('queue')
            : null;

        foreach ($manifest as $suiteEntry) {
            if (! is_array($suiteEntry)) {
                continue;
            }

            foreach (($suiteEntry['chunks'] ?? []) as $chunk) {
                if (! is_array($chunk)) {
                    continue;
                }

                $key = $chunk['key'] ?? null;
                $suite = $chunk['suite'] ?? null;
                $offset = $chunk['offset'] ?? null;
                $limit = $chunk['limit'] ?? null;

                if (! is_string($key) || isset($completedMap[$key])) {
                    continue;
                }

                if (! is_string($suite) || (! is_int($offset) && ! is_float($offset)) || (! is_int($limit) && ! is_float($limit))) {
                    continue;
                }

                $job = new RunEvalChunkJob(
                    $runId,
                    $suite,
                    (int) $offset,
                    (int) $limit,
                    $baseConfig,
                );

                if (is_string($connection) && $connection !== '') {
                    $job->onConnection($connection);
                }

                if (is_string($queue) && $queue !== '') {
                    $job->onQueue($queue);
                }

                dispatch($job);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $run
     */
    private function progressLine(array $run): string
    {
        $total = (int) ($run['queue']['total_chunks'] ?? 0);
        $completed = count(is_array($run['queue']['completed_chunks'] ?? null) ? $run['queue']['completed_chunks'] : []);

        return sprintf('Progress: %d/%d chunks completed', $completed, $total);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseConfig(): array
    {
        $config = config('ai-evals', []);

        return is_array($config) ? $config : [];
    }
}
