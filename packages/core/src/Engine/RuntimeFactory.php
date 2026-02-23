<?php

declare(strict_types=1);

namespace PhpEvals\Core\Engine;

use PhpEvals\Core\Assertions\ContainsAssertion;
use PhpEvals\Core\Assertions\JsonSchemaAssertion;
use PhpEvals\Core\Assertions\NotContainsAssertion;
use PhpEvals\Core\Assertions\RegexAssertion;
use PhpEvals\Core\Assertions\SemanticSimilarityAssertion;
use PhpEvals\Core\Assertions\ToolArgsSchemaAssertion;
use PhpEvals\Core\Assertions\ToolCallCountAssertion;
use PhpEvals\Core\Assertions\ToolCalledAssertion;
use PhpEvals\Core\Config\CoreConfig;
use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Contracts\SimilarityScorer;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;
use PhpEvals\Core\Model\NullModelClient;
use PhpEvals\Core\Scoring\TokenOverlapSimilarityScorer;
use PhpEvals\Core\Support\JsonSchemaValidator;
use ReflectionClass;

final class RuntimeFactory
{
    public function buildModelClient(CoreConfig $config): ModelClient
    {
        $definition = $config->modelClient;

        if ($definition instanceof ModelClient) {
            return $definition;
        }

        if (is_callable($definition)) {
            $client = $definition($config->modelClientOptions, $config);
            if (! $client instanceof ModelClient) {
                throw new RuntimeConfigurationException('model_client callable must return a ModelClient instance.');
            }

            return $client;
        }

        if (is_string($definition)) {
            $instance = $this->instantiate($definition, $config->modelClientOptions, ModelClient::class);

            /** @var ModelClient $instance */
            return $instance;
        }

        return new NullModelClient;
    }

    public function buildSimilarityScorer(CoreConfig $config): SimilarityScorer
    {
        $definition = $config->similarityScorer;

        if ($definition instanceof SimilarityScorer) {
            return $definition;
        }

        if (is_callable($definition)) {
            $scorer = $definition($config->similarityScorerOptions, $config);
            if (! $scorer instanceof SimilarityScorer) {
                throw new RuntimeConfigurationException('similarity_scorer callable must return SimilarityScorer instance.');
            }

            return $scorer;
        }

        if (is_string($definition)) {
            $instance = $this->instantiate($definition, $config->similarityScorerOptions, SimilarityScorer::class);

            /** @var SimilarityScorer $instance */
            return $instance;
        }

        return new TokenOverlapSimilarityScorer;
    }

    public function buildAssertionRegistry(SimilarityScorer $scorer): AssertionRegistry
    {
        $validator = new JsonSchemaValidator;

        return new AssertionRegistry([
            new ContainsAssertion,
            new NotContainsAssertion,
            new RegexAssertion,
            new JsonSchemaAssertion($validator),
            new SemanticSimilarityAssertion($scorer),
            new ToolCalledAssertion,
            new ToolCallCountAssertion,
            new ToolArgsSchemaAssertion($validator),
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function instantiate(string $className, array $options, string $expected): object
    {
        if (! class_exists($className)) {
            throw new RuntimeConfigurationException(sprintf('Configured class "%s" does not exist.', $className));
        }

        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            $instance = $reflection->newInstance();
        } else {
            $parameterCount = count($constructor->getParameters());
            $requiredCount = $constructor->getNumberOfRequiredParameters();

            if ($parameterCount <= 1) {
                $instance = $parameterCount === 0
                    ? $reflection->newInstance()
                    : $reflection->newInstance($options);
            } elseif ($requiredCount <= 1) {
                $instance = $reflection->newInstance($options);
            } else {
                throw new RuntimeConfigurationException(
                    sprintf('Configured class "%s" must have zero or one required constructor argument.', $className),
                );
            }
        }

        if (! $instance instanceof $expected) {
            throw new RuntimeConfigurationException(sprintf('Configured class "%s" must implement %s.', $className, $expected));
        }

        return $instance;
    }
}
