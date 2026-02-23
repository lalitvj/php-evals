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
use PhpEvals\Core\Scoring\TokenOverlapSimilarityScorer;
use PHPUnit\Framework\TestCase;

final class RuntimeFactoryExtraTest extends TestCase
{
    public function test_similarity_scorer_from_class_name(): void
    {
        $factory = new RuntimeFactory;
        $config = CoreConfig::fromArray(['similarity_scorer' => TokenOverlapSimilarityScorer::class]);

        self::assertInstanceOf(SimilarityScorer::class, $factory->buildSimilarityScorer($config));
    }

    public function test_model_client_callable_must_return_contract_instance(): void
    {
        $factory = new RuntimeFactory;
        $config = CoreConfig::fromArray(['model_client' => static fn (): string => 'bad']);

        $this->expectException(RuntimeConfigurationException::class);
        $factory->buildModelClient($config);
    }

    public function test_similarity_callable_must_return_contract_instance(): void
    {
        $factory = new RuntimeFactory;
        $config = CoreConfig::fromArray(['similarity_scorer' => static fn (): string => 'bad']);

        $this->expectException(RuntimeConfigurationException::class);
        $factory->buildSimilarityScorer($config);
    }

    public function test_missing_class_throws_runtime_error(): void
    {
        $factory = new RuntimeFactory;
        $config = CoreConfig::fromArray(['model_client' => 'Nope\\Missing']);

        $this->expectException(RuntimeConfigurationException::class);
        $factory->buildModelClient($config);
    }

    public function test_optional_multi_argument_constructor_is_supported(): void
    {
        $factory = new RuntimeFactory;
        $config = CoreConfig::fromArray([
            'model_client' => RuntimeFactoryOptionalConstructorClient::class,
            'model_client_options' => ['prefix' => 'ok-'],
        ]);

        $client = $factory->buildModelClient($config);

        self::assertSame('ok-value', $client->complete(new ModelRequest('id', 'value'))->output);
    }

    public function test_existing_instances_are_returned_directly(): void
    {
        $factory = new RuntimeFactory;
        $modelClient = new RuntimeFactoryOptionalConstructorClient(['prefix' => 'x-']);
        $scorer = new TokenOverlapSimilarityScorer;

        $config = CoreConfig::fromArray([
            'model_client' => $modelClient,
            'similarity_scorer' => $scorer,
        ]);

        self::assertSame($modelClient, $factory->buildModelClient($config));
        self::assertSame($scorer, $factory->buildSimilarityScorer($config));
    }

    public function test_constructor_with_zero_parameters_is_supported(): void
    {
        $factory = new RuntimeFactory;
        $config = CoreConfig::fromArray(['model_client' => RuntimeFactoryNoArgClient::class]);

        $client = $factory->buildModelClient($config);

        self::assertSame('ok', $client->complete(new ModelRequest('id', 'value'))->output);
    }

    public function test_class_without_expected_interface_throws(): void
    {
        $factory = new RuntimeFactory;
        $config = CoreConfig::fromArray(['model_client' => RuntimeFactoryWrongContract::class]);

        $this->expectException(RuntimeConfigurationException::class);
        $factory->buildModelClient($config);
    }
}

final class RuntimeFactoryOptionalConstructorClient implements ModelClient
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(private readonly array $options = [], private readonly ?string $unused = null) {}

    public function complete(ModelRequest $request): ModelResponse
    {
        $prefix = is_string($this->options['prefix'] ?? null) ? $this->options['prefix'] : '';

        return new ModelResponse($prefix.$request->input);
    }
}

final class RuntimeFactoryNoArgClient implements ModelClient
{
    public function __construct() {}

    public function complete(ModelRequest $request): ModelResponse
    {
        return new ModelResponse('ok');
    }
}

final class RuntimeFactoryWrongContract
{
    public function __construct() {}
}
