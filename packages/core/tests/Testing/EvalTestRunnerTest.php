<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Testing;

use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Testing\EvalTestRunner;
use PHPUnit\Framework\TestCase;

final class EvalTestRunnerTest extends TestCase
{
    private string $datasetPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->datasetPath = sys_get_temp_dir().'/php-evals-testing-'.uniqid('', true);
        mkdir($this->datasetPath, 0777, true);
        file_put_contents(
            $this->datasetPath.'/helpers.jsonl',
            "{\"id\":\"helper-1\",\"input\":\"hello\",\"expected\":{\"assertions\":[{\"type\":\"contains\",\"value\":\"hello\"}]}}\n",
        );
    }

    protected function tearDown(): void
    {
        @unlink($this->datasetPath.'/helpers.jsonl');
        @rmdir($this->datasetPath);

        parent::tearDown();
    }

    public function test_run_suite_returns_zero_when_suite_passes(): void
    {
        $code = EvalTestRunner::runSuite('helpers', [
            'dataset_path' => $this->datasetPath,
            'model_client' => EvalTestRunnerModelClient::class,
        ]);

        self::assertSame(0, $code);
    }

    public function test_run_and_collect_returns_human_readable_output(): void
    {
        $result = EvalTestRunner::runAndCollect('helpers', [
            'dataset_path' => $this->datasetPath,
            'model_client' => EvalTestRunnerModelClient::class,
        ]);

        self::assertSame(0, $result['code']);
        self::assertStringContainsString('Suite: helpers', $result['output']);
    }
}

final class EvalTestRunnerModelClient implements ModelClient
{
    public function complete(ModelRequest $request): ModelResponse
    {
        return ModelResponse::fromArray([
            'output' => sprintf('echo: %s', $request->input),
        ]);
    }
}
