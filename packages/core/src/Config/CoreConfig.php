<?php

declare(strict_types=1);

namespace PhpEvals\Core\Config;

final class CoreConfig
{
    /**
     * @param  array<string, mixed>  $modelClientOptions
     * @param  array<string, mixed>  $similarityScorerOptions
     */
    public function __construct(
        public readonly string $datasetPath,
        public readonly ?string $model,
        public readonly bool $stopOnFailure,
        public readonly string $format,
        public readonly ?string $jsonReportPath,
        public readonly bool $storeRuns,
        public readonly ?string $runId,
        public readonly string $runStoreDriver,
        public readonly string $runStorePath,
        public readonly ?string $runStoreDsn,
        public readonly ?string $runStoreUser,
        public readonly ?string $runStorePassword,
        public readonly bool $deterministic,
        public readonly ?int $seed,
        public readonly bool $cacheEnabled,
        public readonly string $cachePath,
        public readonly ?int $cacheTtl,
        public readonly ?string $replayRunId,
        public readonly ?string $compareBaselineRunId,
        public readonly ?string $compareCandidateRunId,
        /**
         * @var array<string, float>
         */
        public readonly array $failThresholds,
        public readonly mixed $modelClient,
        public readonly array $modelClientOptions,
        public readonly mixed $judgeClient,
        /**
         * @var array<string, mixed>
         */
        public readonly array $judgeClientOptions,
        public readonly mixed $similarityScorer,
        public readonly array $similarityScorerOptions,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $format = is_string($payload['format'] ?? null) ? $payload['format'] : 'table';

        return new self(
            is_string($payload['dataset_path'] ?? null) ? $payload['dataset_path'] : 'storage/ai-evals',
            is_string($payload['model'] ?? null) ? $payload['model'] : null,
            (bool) ($payload['stop_on_failure'] ?? false),
            in_array($format, ['table', 'json'], true) ? $format : 'table',
            is_string($payload['json_report_path'] ?? null) ? $payload['json_report_path'] : null,
            (bool) ($payload['store_runs'] ?? true),
            is_string($payload['run_id'] ?? null) ? $payload['run_id'] : null,
            is_string($payload['run_store_driver'] ?? null) ? $payload['run_store_driver'] : 'file',
            is_string($payload['run_store_path'] ?? null) ? $payload['run_store_path'] : 'storage/ai-evals/runs',
            is_string($payload['run_store_dsn'] ?? null) ? $payload['run_store_dsn'] : null,
            is_string($payload['run_store_user'] ?? null) ? $payload['run_store_user'] : null,
            is_string($payload['run_store_password'] ?? null) ? $payload['run_store_password'] : null,
            (bool) ($payload['deterministic'] ?? false),
            (is_int($payload['seed'] ?? null) || is_float($payload['seed'] ?? null)) ? (int) $payload['seed'] : null,
            (bool) ($payload['cache_enabled'] ?? false),
            is_string($payload['cache_path'] ?? null) ? $payload['cache_path'] : 'storage/ai-evals/cache',
            (is_int($payload['cache_ttl'] ?? null) && $payload['cache_ttl'] > 0) ? $payload['cache_ttl'] : null,
            is_string($payload['replay_run_id'] ?? null) ? $payload['replay_run_id'] : null,
            is_string($payload['compare_baseline_run_id'] ?? null) ? $payload['compare_baseline_run_id'] : null,
            is_string($payload['compare_candidate_run_id'] ?? null) ? $payload['compare_candidate_run_id'] : null,
            self::normalizeThresholds($payload['fail_thresholds'] ?? []),
            $payload['model_client'] ?? null,
            is_array($payload['model_client_options'] ?? null) ? $payload['model_client_options'] : [],
            $payload['judge_client'] ?? null,
            is_array($payload['judge_client_options'] ?? null) ? $payload['judge_client_options'] : [],
            $payload['similarity_scorer'] ?? null,
            is_array($payload['similarity_scorer_options'] ?? null) ? $payload['similarity_scorer_options'] : [],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function withOverrides(array $overrides): self
    {
        return self::fromArray(array_merge($this->toArray(), $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dataset_path' => $this->datasetPath,
            'model' => $this->model,
            'stop_on_failure' => $this->stopOnFailure,
            'format' => $this->format,
            'json_report_path' => $this->jsonReportPath,
            'store_runs' => $this->storeRuns,
            'run_id' => $this->runId,
            'run_store_driver' => $this->runStoreDriver,
            'run_store_path' => $this->runStorePath,
            'run_store_dsn' => $this->runStoreDsn,
            'run_store_user' => $this->runStoreUser,
            'run_store_password' => $this->runStorePassword,
            'deterministic' => $this->deterministic,
            'seed' => $this->seed,
            'cache_enabled' => $this->cacheEnabled,
            'cache_path' => $this->cachePath,
            'cache_ttl' => $this->cacheTtl,
            'replay_run_id' => $this->replayRunId,
            'compare_baseline_run_id' => $this->compareBaselineRunId,
            'compare_candidate_run_id' => $this->compareCandidateRunId,
            'fail_thresholds' => $this->failThresholds,
            'model_client' => $this->modelClient,
            'model_client_options' => $this->modelClientOptions,
            'judge_client' => $this->judgeClient,
            'judge_client_options' => $this->judgeClientOptions,
            'similarity_scorer' => $this->similarityScorer,
            'similarity_scorer_options' => $this->similarityScorerOptions,
        ];
    }

    /**
     * @return array<string, float>
     */
    private static function normalizeThresholds(mixed $thresholds): array
    {
        if (! is_array($thresholds)) {
            return [];
        }

        $normalized = [];
        foreach ($thresholds as $metric => $value) {
            if (! is_string($metric)) {
                continue;
            }

            if (is_int($value) || is_float($value)) {
                $normalized[$metric] = (float) $value;
            }
        }

        return $normalized;
    }
}
