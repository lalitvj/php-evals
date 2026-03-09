# Installation

If you are onboarding for the first time, read:

- `docs/first-time-user-guide.md`

## Requirements
- PHP `^8.2`
- Composer
- Docker (recommended for local consistency)

## Core Package (Monorepo)
From repository root:

```bash
make build
make install
```

The CLI binary is available at:

```bash
packages/core/bin/php-evals
```

If `php-evals/core` is installed into another project as a dependency, the binary is exposed as `vendor/bin/php-evals`.

## Configuration File
Create `php-evals.php` in the project root:

```php
<?php

declare(strict_types=1);

return [
    'dataset_path' => __DIR__.'/storage/ai-evals',
    'model' => null,
    'format' => 'table',
    'stop_on_failure' => false,
    'model_client' => \PhpEvals\Core\Model\ArrayMapModelClient::class,
    'model_client_options' => [
        'responses' => [
            'case-1' => ['output' => 'sample'],
        ],
    ],
];
```

## Laravel Bridge
Enable provider in your Laravel app (if package auto-discovery is disabled):

```php
PhpEvals\Laravel\LaravelEvalsServiceProvider::class,
```

Then run:

```bash
php artisan ai:eval:init
php artisan ai:eval sample
```

Recommended integrations after the sample run:
- `PhpEvals\Laravel\Integrations\Prism\PrismModelClient::class`
- `PhpEvals\Laravel\Integrations\LaravelAI\LaravelAIModelClient::class`

Recommended scoring modes:
- `similarity_scorer => 'local'` and `judge_client => 'local'` for lightweight, API-free checks
- `similarity_scorer => 'openai_embeddings'` and `judge_client => 'openai'` for release-facing eval quality
