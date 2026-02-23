# php-evals

Framework-agnostic PHP evaluation toolkit for LLM output quality, with optional Laravel integration.

## Design Goal
Keep runtime logic framework agnostic while giving Laravel users first-class ergonomics.

### Architecture Split
- `packages/core`: pure PHP eval runtime (contracts, loader, validator, runner, assertions, reporting).
- `packages/laravel`: thin Laravel adapter (service provider, config mapping, Artisan command).

The core package intentionally avoids framework runtime dependencies.  
This boundary is enforced by architecture tests in `packages/core/tests/Architecture`.

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

### Laravel-First Command UX
Use either suite argument or option:
- `php artisan ai:eval refund`
- `php artisan ai:eval --suite=refund`

Additional options map directly to core CLI:
- `--model=...`
- `--format=table|json`
- `--stop-on-failure`
- `--dataset-path=...`
- `--json-report-path=...`
- `--config=...`

Invalid format values fail fast with a clear CLI error.

## How This Differs From Pest/PHPUnit
- Pest/PHPUnit are general test frameworks.
- `php-evals` is a dataset-driven LLM regression layer on top of standard testing.
- It adds eval-specific primitives: suite loading, model abstraction, semantic and tool-call assertions, and CI-friendly eval reporting.
- You still use Pest/PHPUnit for app tests; use `php-evals` to prevent LLM behavior regressions over time.

## Developer Productivity Outcomes
- Faster feedback with focused suites (`refund`, `checkout`, etc.).
- Reusable eval datasets across local/dev/CI.
- Better regression visibility via console + JSON reports.
- Laravel users get native command workflow without coupling core runtime to Laravel internals.

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
