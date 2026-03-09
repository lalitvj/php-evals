<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class InitEvalsArtisanCommand extends Command
{
    protected $signature = 'ai:eval:init {--force : Overwrite generated files if they already exist}';

    protected $description = 'Bootstrap ai-evals config, sample suite, fake client, and CI workflow snippet.';

    public function handle(Filesystem $files): int
    {
        $force = (bool) $this->option('force');

        $configPath = method_exists($this->laravel, 'configPath')
            ? $this->laravel->configPath('ai-evals.php')
            : base_path('config/ai-evals.php');
        $datasetDir = storage_path('ai-evals');
        $suitePath = $datasetDir.DIRECTORY_SEPARATOR.'sample.jsonl';
        $clientPath = app_path('AI/FakeEvalModelClient.php');
        $workflowPath = base_path('.github/workflows/ai-evals.yml');

        $files->ensureDirectoryExists(dirname($configPath));
        $files->ensureDirectoryExists($datasetDir);
        $files->ensureDirectoryExists(dirname($clientPath));
        $files->ensureDirectoryExists(dirname($workflowPath));

        $this->writeFile($files, $configPath, $this->configStub(), $force);
        $this->writeFile($files, $suitePath, $this->sampleSuiteStub(), $force);
        $this->writeFile($files, $clientPath, $this->fakeClientStub(), $force);
        $this->writeFile($files, $workflowPath, $this->workflowStub(), $force);

        $this->info('ai-evals onboarding scaffolded successfully.');
        $this->line('Next: configure model_client in config/ai-evals.php and run `php artisan ai:eval sample`.');

        return 0;
    }

    private function writeFile(Filesystem $files, string $path, string $contents, bool $force): void
    {
        if ($files->exists($path) && ! $force) {
            $this->line(sprintf('Skipped existing file: %s', $path));

            return;
        }

        $files->put($path, $contents);
        $this->line(sprintf('Wrote: %s', $path));
    }

    private function configStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

return [
    'dataset_path' => storage_path('ai-evals'),
    'format' => 'table',
    'stop_on_failure' => false,

    'store_runs' => true,
    'run_store_driver' => env('AI_EVALS_RUN_STORE_DRIVER', 'file'),
    'run_store_path' => storage_path('ai-evals/runs'),
    'cache_enabled' => true,
    'cache_path' => storage_path('ai-evals/cache'),

    // Start with the fake client below, then switch to one of:
    // \PhpEvals\Laravel\Integrations\Prism\PrismModelClient::class
    // \PhpEvals\Laravel\Integrations\LaravelAI\LaravelAIModelClient::class
    'model_client' => \App\AI\FakeEvalModelClient::class,
    'model_client_options' => [],

    // Local mode keeps scoring API-free for fast feedback.
    // For higher-trust scoring, set:
    // 'similarity_scorer' => 'openai_embeddings',
    // 'judge_client' => 'openai',
    'similarity_scorer' => 'local',
    'similarity_scorer_options' => [],
    'judge_client' => 'local',
    'judge_client_options' => [],

    'fail_thresholds' => [
        'pass_rate_drop' => 0.05,
        'avg_score_drop' => 0.05,
    ],
];
PHP;
    }

    private function sampleSuiteStub(): string
    {
        return <<<'JSONL'
{"id":"sample_001","input":"I was charged twice","expected":{"assertions":[{"type":"contains","value":"refund"}]},"metadata":{"goal":"Explain refund process clearly."}}
JSONL;
    }

    private function fakeClientStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\AI;

use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;

final class FakeEvalModelClient implements ModelClient
{
    public function complete(ModelRequest $request): ModelResponse
    {
        return ModelResponse::fromArray([
            'output' => 'To get a refund, share your order ID and we will verify the duplicate charge.',
            'prompt_tokens' => 32,
            'completion_tokens' => 18,
            'cost' => 0.0008,
        ]);
    }
}
PHP;
    }

    private function workflowStub(): string
    {
        return <<<'YAML'
name: ai-evals

on:
  pull_request:
  push:
    branches: [master]

jobs:
  evals:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install --no-interaction --prefer-dist --no-progress
      - run: php artisan ai:eval --format=json --json-report-path=artifacts/evals.json
      - uses: actions/upload-artifact@v4
        if: always()
        with:
          name: ai-evals-report
          path: artifacts/evals.json
YAML;
    }
}
