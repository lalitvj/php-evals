<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Model;

use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Model\CachedModelClient;
use PHPUnit\Framework\TestCase;

final class CachedModelClientTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cachePath = sys_get_temp_dir().'/php-evals-cache-'.uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cachePath)) {
            foreach (glob($this->cachePath.'/*.json') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->cachePath);
        }

        parent::tearDown();
    }

    public function test_caches_provider_response(): void
    {
        $inner = new CachedModelClientTestModelClient;
        $cached = new CachedModelClient($inner, $this->cachePath);

        $request = new ModelRequest('case-1', 'hello', seed: 42);
        $first = $cached->complete($request);
        $second = $cached->complete($request);

        self::assertSame('response-1', $first->output);
        self::assertSame('response-1', $second->output);
        self::assertSame(1, $inner->calls);
    }

    public function test_expired_cache_causes_fresh_call(): void
    {
        $inner = new CachedModelClientTestModelClient;
        $cached = new CachedModelClient($inner, $this->cachePath, ttlSeconds: 1);

        $request = new ModelRequest('case-1', 'hello', seed: 42);
        $cached->complete($request);
        self::assertSame(1, $inner->calls);

        // Backdate the cache file to simulate expiry.
        $files = glob($this->cachePath.'/*.json') ?: [];
        self::assertNotEmpty($files);
        touch($files[0], time() - 10);

        $cached->complete($request);
        self::assertSame(2, $inner->calls);
    }

    public function test_null_ttl_caches_indefinitely(): void
    {
        $inner = new CachedModelClientTestModelClient;
        $cached = new CachedModelClient($inner, $this->cachePath, ttlSeconds: null);

        $request = new ModelRequest('case-1', 'hello', seed: 42);
        $cached->complete($request);

        // Backdate the file — should still be served from cache since TTL is null.
        $files = glob($this->cachePath.'/*.json') ?: [];
        touch($files[0], time() - 86400);

        $cached->complete($request);
        self::assertSame(1, $inner->calls);
    }

    public function test_clear_cache_removes_all_files(): void
    {
        $inner = new CachedModelClientTestModelClient;
        $cached = new CachedModelClient($inner, $this->cachePath);

        $cached->complete(new ModelRequest('case-1', 'hello', seed: 42));
        $cached->complete(new ModelRequest('case-2', 'world', seed: 42));

        $removed = $cached->clearCache();
        self::assertSame(2, $removed);
        self::assertSame([], glob($this->cachePath.'/*.json') ?: []);
    }

    public function test_clear_expired_cache_removes_only_old_files(): void
    {
        $inner = new CachedModelClientTestModelClient;
        $cached = new CachedModelClient($inner, $this->cachePath, ttlSeconds: 60);

        $cached->complete(new ModelRequest('case-1', 'hello', seed: 42));
        $cached->complete(new ModelRequest('case-2', 'world', seed: 42));

        $files = glob($this->cachePath.'/*.json') ?: [];
        self::assertCount(2, $files);

        // Expire only the first file.
        touch($files[0], time() - 120);

        $removed = $cached->clearExpiredCache();
        self::assertSame(1, $removed);
        self::assertCount(1, glob($this->cachePath.'/*.json') ?: []);
    }
}

final class CachedModelClientTestModelClient implements ModelClient
{
    public int $calls = 0;

    public function complete(ModelRequest $request): ModelResponse
    {
        $this->calls++;

        return ModelResponse::fromArray(['output' => 'response-'.$this->calls]);
    }
}
