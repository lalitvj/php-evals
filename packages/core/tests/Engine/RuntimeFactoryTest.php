<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Engine;

use PhpEvals\Core\Config\CoreConfig;
use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Contracts\SimilarityScorer;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Engine\RuntimeFactory;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;
use PhpEvals\Core\Model\NullModelClient;
use PHPUnit\Framework\TestCase;

final class RuntimeFactoryTest extends TestCase
{
    public function test_uses_default_implementations(): void
    {
        $factory = new RuntimeFactory;
        $config = CoreConfig::fromArray([]);

        self::assertInstanceOf(NullModelClient::class, $factory->buildModelClient($config));
        self::assertInstanceOf(SimilarityScorer::class, $factory->buildSimilarityScorer($config));
    }

    public function test_builds_model_client_from_class_name(): void
    {
        $factory = new RuntimeFactory;
        $config = CoreConfig::fromArray([
            'model_client' => RuntimeFactoryModelClient::class,
            'model_client_options' => ['suffix' => '-ok'],
        ]);

        $client = $factory->buildModelClient($config);
        $response = $client->complete(new ModelRequest('id', 'hello'));

        self::assertSame('hello-ok', $response->output);
    }

    public function test_throws_for_invalid_model_client_configuration(): void
    {
        $factory = new RuntimeFactory;
        $config = CoreConfig::fromArray(['model_client' => RuntimeFactoryInvalidModelClient::class]);

        $this->expectException(RuntimeConfigurationException::class);
        $factory->buildModelClient($config);
    }

    public function test_builds_from_callable_configuration(): void
    {
        $factory = new RuntimeFactory;
        $config = CoreConfig::fromArray([
            'model_client' => static fn (): ModelClient => new RuntimeFactoryModelClient,
            'similarity_scorer' => static fn (): SimilarityScorer => new RuntimeFactoryScorer,
        ]);

        self::assertInstanceOf(ModelClient::class, $factory->buildModelClient($config));
        self::assertSame(1.0, $factory->buildSimilarityScorer($config)->score('a', 'b'));
    }
}

final class RuntimeFactoryModelClient implements ModelClient
{
    private string $suffix;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $this->suffix = is_string($options['suffix'] ?? null) ? $options['suffix'] : '';
    }

    public function complete(ModelRequest $request): ModelResponse
    {
        return new ModelResponse($request->input.$this->suffix);
    }
}

final class RuntimeFactoryInvalidModelClient
{
    public function __construct(string $first, string $second) {}
}

final class RuntimeFactoryScorer implements SimilarityScorer
{
    public function score(string $actual, string $reference): float
    {
        return 1.0;
    }
}
