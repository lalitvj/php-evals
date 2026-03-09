<?php

declare(strict_types=1);

return [
    'dataset_path' => 'storage/ai-evals',
    'model' => null,
    'format' => 'table',
    'stop_on_failure' => false,
    'json_report_path' => null,
    'store_runs' => true,
    'run_id' => null,
    'run_store_driver' => env('AI_EVALS_RUN_STORE_DRIVER', 'file'),
    'run_store_path' => storage_path('ai-evals/runs'),
    'run_store_dsn' => env('AI_EVALS_RUN_STORE_DSN'),
    'run_store_user' => env('AI_EVALS_RUN_STORE_USER'),
    'run_store_password' => env('AI_EVALS_RUN_STORE_PASSWORD'),
    'deterministic' => false,
    'seed' => null,
    'cache_enabled' => env('AI_EVALS_CACHE_ENABLED', false),
    'cache_path' => storage_path('ai-evals/cache'),
    'cache_ttl' => env('AI_EVALS_CACHE_TTL'),
    'replay_run_id' => null,
    'compare_baseline_run_id' => null,
    'compare_candidate_run_id' => null,
    'fail_thresholds' => [
        'pass_rate_drop' => 0.05,
        'avg_score_drop' => 0.05,
    ],
    'queue' => [
        'connection' => env('AI_EVALS_QUEUE_CONNECTION'),
        'queue' => env('AI_EVALS_QUEUE_NAME', 'default'),
        'chunk_size' => 25,
    ],

    // Recommended Laravel-native integrations:
    // - Prism: \PhpEvals\Laravel\Integrations\Prism\PrismModelClient::class
    // - Laravel AI: \PhpEvals\Laravel\Integrations\LaravelAI\LaravelAIModelClient::class
    'model_client' => null,
    'model_client_options' => [],

    // Scoring tiers:
    // - 'local' / 'heuristic' keeps everything lightweight and API-free.
    // - 'openai' and 'openai_embeddings' use real OpenAI-backed evaluation.
    'judge_client' => 'local',
    'judge_client_options' => [],

    'similarity_scorer' => 'local',
    'similarity_scorer_options' => [],
];
