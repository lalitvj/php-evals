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
