# php-evals

Framework-agnostic PHP evaluation toolkit for LLM output quality, with optional Laravel integration.

## Requirements
- PHP `^8.2`
- Docker (recommended for consistent local setup)

## Local Development
1. `make build`
2. `make install`
3. `make qa`

## Core CLI Usage
Create `php-evals.php` in your project root:

```php
<?php

declare(strict_types=1);

return [
    'dataset_path' => __DIR__.'/storage/ai-evals',
    'model_client' => \PhpEvals\Core\Model\ArrayMapModelClient::class,
    'model_client_options' => [
        'responses' => [
            'refund_001' => ['output' => 'Refund is available.'],
        ],
    ],
];
```

Create a suite file `storage/ai-evals/refund.jsonl`:

```json
{"id":"refund_001","input":"I was charged twice","expected":{"assertions":[{"type":"contains","value":"Refund"}]}}
```

Run:
- `packages/core/bin/php-evals`
- `packages/core/bin/php-evals --suite=refund --format=json`
- `packages/core/bin/php-evals --suite=refund --format=json --json-report-path=artifacts/evals.json`

## Assertion Types
- `contains`
- `not_contains`
- `regex`
- `json_schema`
- `semantic_similarity`
- `tool_called`
- `tool_call_count`
- `tool_args_schema`

## Laravel Bridge
The repository includes `packages/laravel` with:
- `LaravelEvalsServiceProvider`
- Artisan command: `php artisan ai:eval`

## Repository Layout
- `packages/core`: framework-agnostic runtime and CLI.
- `packages/laravel`: optional Laravel bridge.
- `docs`: usage and contribution notes.

## Documentation
- `docs/installation.md`
- `docs/quickstart-core.md`
- `docs/quickstart-laravel.md`
- `docs/assertions.md`
- `docs/function-calling.md`
- `docs/ci.md`
- `docs/extending.md`
- `docs/release-checklist.md`

## Test Helper
`PhpEvals\Core\Testing\EvalTestRunner` provides utilities to run suites from PHPUnit/Pest tests.

## Quality Commands
- `make test`
- `make analyse`
- `make format-check`
- `make qa`
