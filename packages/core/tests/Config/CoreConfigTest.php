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
        self::assertNull($config->cacheTtl);
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

    public function test_cache_ttl_parsed_when_positive_integer(): void
    {
        $config = CoreConfig::fromArray(['cache_ttl' => 3600]);

        self::assertSame(3600, $config->cacheTtl);
    }

    public function test_cache_ttl_null_when_zero_or_negative(): void
    {
        self::assertNull(CoreConfig::fromArray(['cache_ttl' => 0])->cacheTtl);
        self::assertNull(CoreConfig::fromArray(['cache_ttl' => -1])->cacheTtl);
    }

    public function test_cache_ttl_null_when_non_integer(): void
    {
        self::assertNull(CoreConfig::fromArray(['cache_ttl' => 'abc'])->cacheTtl);
        self::assertNull(CoreConfig::fromArray(['cache_ttl' => null])->cacheTtl);
    }

    public function test_cache_ttl_roundtrips_through_to_array(): void
    {
        $config = CoreConfig::fromArray(['cache_ttl' => 7200]);
        $restored = CoreConfig::fromArray($config->toArray());

        self::assertSame(7200, $restored->cacheTtl);
    }
}
