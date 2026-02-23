<?php

declare(strict_types=1);

return [
    'dataset_path' => 'storage/ai-evals',
    'model' => null,
    'format' => 'table',
    'stop_on_failure' => false,
    'json_report_path' => null,

    // Set this to your implementation class or closure.
    'model_client' => null,
    'model_client_options' => [],

    // Optional scorer override.
    'similarity_scorer' => null,
    'similarity_scorer_options' => [],
];
