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

    // Set this to your implementation class or closure.
    'model_client' => null,
    'model_client_options' => [],
    'judge_client' => null,
    'judge_client_options' => [],

    // Optional scorer override.
    'similarity_scorer' => null,
    'similarity_scorer_options' => [],
];
