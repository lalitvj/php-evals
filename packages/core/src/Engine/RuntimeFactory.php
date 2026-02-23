<?php

declare(strict_types=1);

namespace PhpEvals\Core\Engine;

use PDO;
use PhpEvals\Core\Assertions\ContainsAssertion;
use PhpEvals\Core\Assertions\GoalCompletionAssertion;
use PhpEvals\Core\Assertions\JsonSchemaAssertion;
use PhpEvals\Core\Assertions\LlmJudgeRubricAssertion;
use PhpEvals\Core\Assertions\NotContainsAssertion;
use PhpEvals\Core\Assertions\RagContextPrecisionAssertion;
use PhpEvals\Core\Assertions\RagFaithfulnessAssertion;
use PhpEvals\Core\Assertions\RagRelevanceAssertion;
use PhpEvals\Core\Assertions\RegexAssertion;
use PhpEvals\Core\Assertions\SemanticSimilarityAssertion;
use PhpEvals\Core\Assertions\ToolArgsSchemaAssertion;
use PhpEvals\Core\Assertions\ToolCallAccuracyAssertion;
use PhpEvals\Core\Assertions\ToolCallCountAssertion;
use PhpEvals\Core\Assertions\ToolCalledAssertion;
use PhpEvals\Core\Config\CoreConfig;
use PhpEvals\Core\Contracts\JudgeClient;
use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Contracts\RunStore;
use PhpEvals\Core\Contracts\SimilarityScorer;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;
use PhpEvals\Core\Model\CachedModelClient;
use PhpEvals\Core\Model\HeuristicJudgeClient;
use PhpEvals\Core\Model\NullModelClient;
use PhpEvals\Core\Model\ReplayModelClient;
use PhpEvals\Core\Scoring\TokenOverlapSimilarityScorer;
use PhpEvals\Core\Storage\FileRunStore;
use PhpEvals\Core\Storage\PdoRunStore;
use PhpEvals\Core\Support\JsonSchemaValidator;
use ReflectionClass;

final class RuntimeFactory
{
    public function buildModelClient(CoreConfig $config): ModelClient
    {
        $definition = $config->modelClient;
        $client = null;

        if ($definition instanceof ModelClient) {
            $client = $definition;
        }

        if ($client === null && is_callable($definition)) {
            $client = $definition($config->modelClientOptions, $config);
            if (! $client instanceof ModelClient) {
                throw new RuntimeConfigurationException('model_client callable must return a ModelClient instance.');
            }
        }

        if ($client === null && is_string($definition)) {
            $instance = $this->instantiate($definition, $config->modelClientOptions, ModelClient::class);

            /** @var ModelClient $instance */
            $client = $instance;
        }

        if (! $client instanceof ModelClient) {
            $client = new NullModelClient;
        }

        if ($config->replayRunId !== null) {
            $runStore = $this->buildRunStore($config);
            $run = $runStore->getRun($config->replayRunId);
            $replayResponses = [];
            foreach (($run['suites'] ?? []) as $suite) {
                if (! is_array($suite)) {
                    continue;
                }

                foreach (($suite['cases'] ?? []) as $case) {
                    if (! is_array($case)) {
                        continue;
                    }

                    $caseId = $case['id'] ?? null;
                    $response = $case['response'] ?? null;
                    if (is_string($caseId) && is_array($response)) {
                        $replayResponses[$caseId] = $response;
                    }
                }
            }

            $client = new ReplayModelClient($replayResponses, $client);
        }

        if ($config->cacheEnabled) {
            $client = new CachedModelClient($client, $config->cachePath);
        }

        return $client;
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

    public function buildAssertionRegistry(SimilarityScorer $scorer, JudgeClient $judgeClient): AssertionRegistry
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
            new LlmJudgeRubricAssertion($judgeClient),
            new RagFaithfulnessAssertion,
            new RagRelevanceAssertion($scorer),
            new RagContextPrecisionAssertion,
            new ToolCallAccuracyAssertion,
            new GoalCompletionAssertion($scorer),
        ]);
    }

    public function buildRunStore(CoreConfig $config): RunStore
    {
        if ($config->runStoreDriver === 'database') {
            if ($config->runStoreDsn === null || $config->runStoreDsn === '') {
                throw new RuntimeConfigurationException('run_store_dsn is required when run_store_driver=database.');
            }

            $pdo = new PDO(
                $config->runStoreDsn,
                $config->runStoreUser,
                $config->runStorePassword,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );

            return new PdoRunStore($pdo);
        }

        return new FileRunStore($config->runStorePath);
    }

    public function buildJudgeClient(CoreConfig $config): JudgeClient
    {
        $definition = $config->judgeClient;

        if ($definition instanceof JudgeClient) {
            return $definition;
        }

        if (is_callable($definition)) {
            $client = $definition($config->judgeClientOptions, $config);
            if (! $client instanceof JudgeClient) {
                throw new RuntimeConfigurationException('judge_client callable must return JudgeClient instance.');
            }

            return $client;
        }

        if (is_string($definition)) {
            $instance = $this->instantiate($definition, $config->judgeClientOptions, JudgeClient::class);

            /** @var JudgeClient $instance */
            return $instance;
        }

        return new HeuristicJudgeClient;
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
