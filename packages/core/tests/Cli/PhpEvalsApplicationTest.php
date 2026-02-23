<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Cli;

use PhpEvals\Core\Cli\PhpEvalsApplication;
use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PHPUnit\Framework\TestCase;

final class PhpEvalsApplicationTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/php-evals-cli-'.uniqid('', true);
        mkdir($this->root, 0777, true);
        mkdir($this->root.'/datasets', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_help_option_returns_zero(): void
    {
        $application = new PhpEvalsApplication([], $this->root);
        $lines = [];
        $code = $application->run(['php-evals', '--help'], static function (string $line) use (&$lines): void {
            $lines[] = $line;
        });

        self::assertSame(0, $code);
        self::assertStringContainsString('Options:', implode("\n", $lines));
    }

    public function test_help_option_with_default_writer_is_supported(): void
    {
        $application = new PhpEvalsApplication([], $this->root);

        ob_start();
        $code = $application->run(['php-evals', '--help']);
        ob_end_clean();

        self::assertSame(0, $code);
    }

    public function test_missing_suites_returns_error_exit_code(): void
    {
        $application = new PhpEvalsApplication(['dataset_path' => $this->root.'/datasets'], $this->root);
        $lines = [];
        $code = $application->run(['php-evals'], static function (string $line) use (&$lines): void {
            $lines[] = $line;
        });

        self::assertSame(2, $code);
        self::assertStringContainsString('No suites found', implode("\n", $lines));
    }

    public function test_run_passes_and_writes_json_report(): void
    {
        file_put_contents(
            $this->root.'/datasets/refund.jsonl',
            "{\"id\":\"case-1\",\"input\":\"refund\",\"expected\":{\"assertions\":[{\"type\":\"contains\",\"value\":\"refund\"}]}}\n",
        );

        $reportPath = $this->root.'/report.json';

        $application = new PhpEvalsApplication([
            'dataset_path' => $this->root.'/datasets',
            'model_client' => PhpEvalsCliModelClient::class,
            'model_client_options' => ['responses' => ['case-1' => ['output' => 'refund accepted']]],
            'format' => 'json',
        ], $this->root);

        $lines = [];
        $code = $application->run([
            'php-evals',
            '--json-report-path='.$reportPath,
        ], static function (string $line) use (&$lines): void {
            $lines[] = $line;
        });

        self::assertSame(0, $code);
        self::assertFileExists($reportPath);
        self::assertStringContainsString('"suites"', implode("\n", $lines));
    }

    public function test_run_returns_one_when_assertions_fail(): void
    {
        file_put_contents(
            $this->root.'/datasets/fail.jsonl',
            "{\"id\":\"case-1\",\"input\":\"refund\",\"expected\":{\"assertions\":[{\"type\":\"contains\",\"value\":\"refund\"}]}}\n",
        );

        $application = new PhpEvalsApplication([
            'dataset_path' => $this->root.'/datasets',
            'model_client' => PhpEvalsCliModelClient::class,
            'model_client_options' => ['responses' => ['case-1' => ['output' => 'hello']]],
        ], $this->root);

        $code = $application->run(['php-evals', '--suite=fail'], static function (): void {});

        self::assertSame(1, $code);
    }

    public function test_invalid_model_client_configuration_returns_two(): void
    {
        file_put_contents(
            $this->root.'/datasets/refund.jsonl',
            "{\"id\":\"case-1\",\"input\":\"refund\",\"expected\":{\"assertions\":[]}}\n",
        );

        $application = new PhpEvalsApplication([
            'dataset_path' => $this->root.'/datasets',
            'model_client' => 'Invalid\\MissingClass',
        ], $this->root);

        $code = $application->run(['php-evals'], static function (): void {});

        self::assertSame(2, $code);
    }

    public function test_eval_execution_error_returns_two(): void
    {
        file_put_contents(
            $this->root.'/datasets/runtime.jsonl',
            "{\"id\":\"case-1\",\"input\":\"refund\",\"expected\":{\"assertions\":[]}}\n",
        );

        $application = new PhpEvalsApplication([
            'dataset_path' => $this->root.'/datasets',
        ], $this->root);

        $code = $application->run(['php-evals', '--suite=runtime'], static function (): void {});

        self::assertSame(2, $code);
    }

    public function test_configuration_file_is_loaded_when_present(): void
    {
        file_put_contents(
            $this->root.'/datasets/file-suite.jsonl',
            "{\"id\":\"case-1\",\"input\":\"refund\",\"expected\":{\"assertions\":[{\"type\":\"contains\",\"value\":\"refund\"}]}}\n",
        );

        file_put_contents(
            $this->root.'/php-evals.php',
            "<?php\nreturn [\n  'dataset_path' => '".$this->root."/datasets',\n  'model_client' => '".PhpEvalsCliModelClient::class."',\n  'model_client_options' => ['responses' => ['case-1' => ['output' => 'refund done']]],\n];\n",
        );

        $application = new PhpEvalsApplication([], $this->root);
        $code = $application->run(['php-evals', 'ignored-arg', '--suite=file-suite', '--dataset-path='.$this->root.'/datasets'], static function (): void {});

        self::assertSame(0, $code);
    }

    public function test_explicit_config_option_is_respected(): void
    {
        file_put_contents(
            $this->root.'/datasets/explicit.jsonl',
            "{\"id\":\"case-1\",\"input\":\"refund\",\"expected\":{\"assertions\":[{\"type\":\"contains\",\"value\":\"refund\"}]}}\n",
        );

        $configPath = $this->root.'/custom-config.php';
        file_put_contents(
            $configPath,
            "<?php\nreturn [\n  'dataset_path' => '".$this->root."/datasets',\n  'model_client' => '".PhpEvalsCliModelClient::class."',\n  'model_client_options' => ['responses' => ['case-1' => ['output' => 'refund done']]],\n];\n",
        );

        $application = new PhpEvalsApplication([], $this->root);
        $code = $application->run(['php-evals', '--config='.$configPath, '--suite=explicit'], static function (): void {});

        self::assertSame(0, $code);
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $child = $path.DIRECTORY_SEPARATOR.$item;
            if (is_dir($child)) {
                $this->deleteDirectory($child);
            } else {
                @unlink($child);
            }
        }

        @rmdir($path);
    }
}

final class PhpEvalsCliModelClient implements ModelClient
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
