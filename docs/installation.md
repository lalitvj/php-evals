# Installation

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
php artisan ai:eval
```
