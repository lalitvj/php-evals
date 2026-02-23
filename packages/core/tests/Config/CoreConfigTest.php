<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Config;

use PhpEvals\Core\Config\CoreConfig;
use PHPUnit\Framework\TestCase;

final class CoreConfigTest extends TestCase
{
    public function test_from_array_uses_defaults(): void
    {
        $config = CoreConfig::fromArray([]);

        self::assertSame('storage/ai-evals', $config->datasetPath);
        self::assertNull($config->model);
        self::assertFalse($config->stopOnFailure);
        self::assertSame('table', $config->format);
        self::assertNull($config->jsonReportPath);
        self::assertNull($config->modelClient);
        self::assertSame([], $config->modelClientOptions);
    }

    public function test_from_array_normalizes_invalid_format(): void
    {
        $config = CoreConfig::fromArray(['format' => 'xml']);

        self::assertSame('table', $config->format);
    }

    public function test_with_overrides_applies_updates(): void
    {
        $config = CoreConfig::fromArray(['dataset_path' => '/tmp/a'])->withOverrides(['dataset_path' => '/tmp/b', 'format' => 'json']);

        self::assertSame('/tmp/b', $config->datasetPath);
        self::assertSame('json', $config->format);
    }
}
