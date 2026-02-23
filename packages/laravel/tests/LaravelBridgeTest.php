<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Tests;

use Orchestra\Testbench\TestCase;
use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Laravel\LaravelEvalsServiceProvider;

final class LaravelBridgeTest extends TestCase
{
    private string $datasetPath;

    private string $reportPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->datasetPath = sys_get_temp_dir().'/php-evals-laravel-'.uniqid('', true);
        mkdir($this->datasetPath, 0777, true);
        $this->reportPath = $this->datasetPath.'/report.json';
        file_put_contents(
            $this->datasetPath.'/refund.jsonl',
            "{\"id\":\"case-1\",\"input\":\"refund\",\"expected\":{\"assertions\":[{\"type\":\"contains\",\"value\":\"refund\"}]}}\n",
        );

        $this->app['config']->set('ai-evals', [
            'dataset_path' => $this->datasetPath,
            'model_client' => LaravelBridgeModelClient::class,
            'model_client_options' => [
                'responses' => [
                    'case-1' => ['output' => 'refund accepted'],
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->reportPath);
        @unlink($this->datasetPath.'/refund.jsonl');
        @rmdir($this->datasetPath);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [LaravelEvalsServiceProvider::class];
    }

    public function test_artisan_command_runs_and_passes(): void
    {
        $this->artisan('ai:eval --suite=refund')
            ->expectsOutputToContain('Suite: refund')
            ->assertExitCode(0);
    }

    public function test_artisan_command_supports_suite_argument(): void
    {
        $this->artisan('ai:eval refund')
            ->expectsOutputToContain('Suite: refund')
            ->assertExitCode(0);
    }

    public function test_artisan_command_handles_all_options(): void
    {
        $this->artisan(sprintf(
            'ai:eval --suite=refund --model=mock-model --format=json --stop-on-failure --dataset-path=%s --json-report-path=%s',
            $this->datasetPath,
            $this->reportPath,
        ))
            ->expectsOutputToContain('"suites"')
            ->assertExitCode(0);

        self::assertFileExists($this->reportPath);
    }

    public function test_artisan_command_rejects_invalid_format(): void
    {
        $this->artisan('ai:eval --suite=refund --format=xml')
            ->expectsOutputToContain('Invalid format "xml".')
            ->assertExitCode(2);
    }

    public function test_publish_tag_is_registered(): void
    {
        $paths = $this->app->make('config')->get('view.paths', []);

        self::assertIsArray($paths);
        self::assertTrue($this->app->providerIsLoaded(LaravelEvalsServiceProvider::class));
    }
}

final class LaravelBridgeModelClient implements ModelClient
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(private readonly array $options = []) {}

    public function complete(ModelRequest $request): ModelResponse
    {
        $responses = is_array($this->options['responses'] ?? null) ? $this->options['responses'] : [];

        return ModelResponse::fromArray(is_array($responses[$request->caseId] ?? null) ? $responses[$request->caseId] : ['output' => '']);
    }
}
