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
        public readonly mixed $modelClient,
        public readonly array $modelClientOptions,
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
            $payload['model_client'] ?? null,
            is_array($payload['model_client_options'] ?? null) ? $payload['model_client_options'] : [],
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
            'model_client' => $this->modelClient,
            'model_client_options' => $this->modelClientOptions,
            'similarity_scorer' => $this->similarityScorer,
            'similarity_scorer_options' => $this->similarityScorerOptions,
        ];
    }
}
