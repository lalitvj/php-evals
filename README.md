# php-evals

## Introduction
`php-evals` is a framework-agnostic PHP toolkit for evaluating LLM behavior with regression-friendly datasets.

It is designed with a split architecture:
- `packages/core`: pure PHP runtime (no framework coupling)
- `packages/laravel`: thin Laravel adapter for first-class DX

This lets non-Laravel projects use the same engine, while Laravel projects get native command ergonomics.

## Key Features
- Framework-agnostic core runtime with typed contracts and DTOs
- JSONL suite loading and validation with actionable errors
- Assertion system for common LLM checks:
  - `contains`, `not_contains`, `regex`, `json_schema`
  - `semantic_similarity`
  - `tool_called`, `tool_call_count`, `tool_args_schema`
- CLI with CI-friendly exit behavior and report output
- Laravel bridge with `php artisan ai:eval`
- Test helper for integrating eval runs into PHPUnit/Pest

## Getting Started
### Requirements
- PHP `^8.2`
- Docker (recommended for local consistency)

### Local Setup
```bash
make build
make install
make qa
```

### Minimal Core Usage
Create `php-evals.php` at project root:

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

Create `storage/ai-evals/refund.jsonl`:

```json
{"id":"refund_001","input":"I was charged twice","expected":{"assertions":[{"type":"contains","value":"Refund"}]}}
```

Run:

```bash
packages/core/bin/php-evals
packages/core/bin/php-evals --suite=refund --format=json
packages/core/bin/php-evals --suite=refund --format=json --json-report-path=artifacts/evals.json
```

### Minimal Laravel Usage
After registering the Laravel bridge package:

```bash
php artisan ai:eval refund
```

You can also use:

```bash
php artisan ai:eval --suite=refund --format=json --stop-on-failure
```

## Feature Details
### Core CLI Options
- `--suite=<name>`
- `--model=<name>`
- `--format=table|json`
- `--stop-on-failure`
- `--dataset-path=<path>`
- `--json-report-path=<path>`
- `--config=<path>`

### Laravel Command Options
`ai:eval` supports:
- `suite` argument or `--suite=` option
- `--model=`
- `--format=table|json`
- `--stop-on-failure`
- `--dataset-path=`
- `--json-report-path=`
- `--config=`

### How It Complements Pest/PHPUnit
- Pest/PHPUnit are test frameworks.
- `php-evals` is a dataset-driven LLM evaluation layer.
- Use Pest/PHPUnit for app logic tests; use `php-evals` for AI behavior regression checks over time.

### Repo Layout
- `packages/core`: framework-agnostic runtime and CLI
- `packages/laravel`: Laravel bridge
- `docs`: installation, quickstarts, assertions, CI, extension guides

## Contribution Note
Contributions are welcome.

Before opening a PR:
```bash
make qa
```

Please keep these principles:
- Core stays framework agnostic
- Laravel DX improvements stay in the adapter layer
- Tests and docs must be updated with behavior changes

See `docs/CONTRIBUTING.md` for workflow details.
