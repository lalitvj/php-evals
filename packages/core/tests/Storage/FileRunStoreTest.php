<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Storage;

use PhpEvals\Core\Storage\FileRunStore;
use PHPUnit\Framework\TestCase;

final class FileRunStoreTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/php-evals-runs-'.uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->directory)) {
            foreach (glob($this->directory.'/*.json') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->directory);
        }

        parent::tearDown();
    }

    public function test_create_update_and_get_run(): void
    {
        $store = new FileRunStore($this->directory);
        $runId = $store->createRun(['status' => 'running']);

        $run = $store->getRun($runId);
        self::assertIsArray($run);
        self::assertSame('running', $run['status']);

        $store->updateRun($runId, ['status' => 'completed', 'summary' => ['passed_cases' => 2]]);
        $updated = $store->getRun($runId);

        self::assertIsArray($updated);
        self::assertSame('completed', $updated['status']);
        self::assertSame(2, $updated['summary']['passed_cases']);
        self::assertNotEmpty($store->listRuns());
    }
}
