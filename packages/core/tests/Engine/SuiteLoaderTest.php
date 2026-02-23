<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Engine;

use PhpEvals\Core\Engine\CaseValidator;
use PhpEvals\Core\Engine\SuiteLoader;
use PhpEvals\Core\Exceptions\InvalidEvalCaseException;
use PHPUnit\Framework\TestCase;

final class SuiteLoaderTest extends TestCase
{
    private string $datasetPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->datasetPath = sys_get_temp_dir().'/php-evals-'.uniqid('', true);
        mkdir($this->datasetPath, 0777, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->datasetPath.'/sample.jsonl');
        @unlink($this->datasetPath.'/bad.jsonl');
        @rmdir($this->datasetPath);

        parent::tearDown();
    }

    public function test_lists_and_loads_suite(): void
    {
        file_put_contents(
            $this->datasetPath.'/sample.jsonl',
            "\n{\"id\":\"a\",\"input\":\"hello\",\"expected\":{\"assertions\":[]}}\n",
        );

        $loader = new SuiteLoader($this->datasetPath, new CaseValidator);

        self::assertSame(['sample'], $loader->listSuites());
        self::assertCount(1, $loader->loadSuite('sample'));
    }

    public function test_invalid_json_row_throws_exception(): void
    {
        file_put_contents($this->datasetPath.'/bad.jsonl', "{not-json}\n");

        $loader = new SuiteLoader($this->datasetPath, new CaseValidator);

        $this->expectException(InvalidEvalCaseException::class);
        $loader->loadSuite('bad');
    }

    public function test_missing_suite_throws_exception(): void
    {
        $loader = new SuiteLoader($this->datasetPath, new CaseValidator);

        $this->expectException(InvalidEvalCaseException::class);
        $loader->loadSuite('missing');
    }
}
