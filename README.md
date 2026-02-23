# php-evals

## Introduction
`php-evals` is a framework-agnostic PHP evaluation toolkit for LLM apps.

It provides a core runtime (`packages/core`) and a Laravel-first adapter (`packages/laravel`) so you can keep evaluation logic portable while giving Laravel teams a faster onboarding path.

## Key Features
- Baseline vs candidate comparison with regression thresholds and CI-friendly exit codes
- Persistent run storage:
  - local file store
  - database store (PDO in core + Laravel migration)
- Rich run metadata: per-case score, latency, tokens, cost, plus suite/run aggregates
- Evaluator packs beyond string checks:
  - `llm_judge_rubric`
  - `rag_faithfulness`, `rag_relevance`, `rag_context_precision`
  - `goal_completion`, `tool_call_accuracy`
  - core checks (`contains`, `not_contains`, `regex`, `json_schema`, `semantic_similarity`, tool assertions)
- Deterministic mode + replayability:
  - request seed support
  - provider response cache
  - replay responses from a stored run
- Laravel-first DX:
  - `php artisan ai:eval:init`
  - `php artisan ai:eval`
  - `php artisan ai:eval:compare`
  - `php artisan ai:eval:queue`
  - `php artisan ai:eval:progress`

## Getting Started
### Requirements
- PHP `^8.2`
- Docker (recommended)

### Local Setup
```bash
make build
make install
make qa
```

### Core Quickstart
Create `php-evals.php`:

```php
<?php

declare(strict_types=1);

return [
    'dataset_path' => __DIR__.'/storage/ai-evals',
    'store_runs' => true,
    'run_store_driver' => 'file',
    'run_store_path' => __DIR__.'/storage/ai-evals/runs',
    'cache_enabled' => true,
    'cache_path' => __DIR__.'/storage/ai-evals/cache',
    'model_client' => \PhpEvals\Core\Model\ArrayMapModelClient::class,
    'model_client_options' => [
        'responses' => [
            'refund_001' => ['output' => 'Refund policy allows returns within 30 days.'],
        ],
    ],
];
```

Create `storage/ai-evals/refund.jsonl`:

```json
{"id":"refund_001","input":"I was charged twice","expected":{"assertions":[{"type":"contains","value":"Refund"}]}}
```

Run evals:

```bash
packages/core/bin/php-evals --suite=refund --store-runs
packages/core/bin/php-evals --suite=refund --format=json --json-report-path=artifacts/evals.json
```

Create baseline/candidate comparison:

```bash
packages/core/bin/php-evals --suite=refund --store-runs --run-id=baseline
packages/core/bin/php-evals --suite=refund --store-runs --run-id=candidate \
  --compare-baseline-run-id=baseline \
  --compare-candidate-run-id=candidate \
  --fail-threshold=pass_rate_drop:0.02,avg_score_drop:0.05
```

### Laravel Quickstart
1. Publish/init scaffolding:
```bash
php artisan ai:eval:init
```

2. Run first suite:
```bash
php artisan ai:eval sample
```

3. Compare runs:
```bash
php artisan ai:eval --suite=sample --store-runs --run-id=baseline
php artisan ai:eval --suite=sample --store-runs --run-id=candidate
php artisan ai:eval:compare baseline candidate --fail-threshold=pass_rate_drop:0.02
```

4. Queue large suites:
```bash
php artisan ai:eval:queue --suite=sample --chunk-size=25
php artisan ai:eval:progress <run-id>
php artisan ai:eval:queue --resume-run-id=<run-id>
```

## Feature Details
### Deterministic + Replay
- `--deterministic` + `--seed=<int>` to reduce run variance
- `--cache-enabled` + `--cache-path=<path>` to reuse provider responses
- `--replay-run-id=<run_id>` to replay stored outputs for reproducible debugging

### Run Store
- File driver:
  - `--run-store-driver=file`
  - `--run-store-path=storage/ai-evals/runs`
- Database driver:
  - `--run-store-driver=database`
  - `--run-store-dsn=...`
  - `--run-store-user=...`
  - `--run-store-password=...`
- Laravel includes migration publishing/loading for `ai_eval_runs`.

### CI Quality Gates
- JSON output includes summary metrics and per-case details.
- Comparison output includes:
  - regression metrics (`pass_rate_drop`, `avg_score_drop`, latency/cost deltas)
  - threshold violations
  - per-case change listing
- Process exits non-zero when:
  - assertions fail
  - configured comparison thresholds are violated

### Framework Agnostic Core, Laravel-First DX
- Core runtime has no framework coupling.
- Laravel adapter adds commands, queue integration, config conventions, and onboarding scaffolds.
- This complements PHPUnit/Pest rather than replacing them: use tests for deterministic app behavior; use `php-evals` for model behavior regression and quality tracking.

## Documentation
Start here if you are new:
- `docs/first-time-user-guide.md`

Then use:
- `docs/installation.md`
- `docs/quickstart-core.md`
- `docs/quickstart-laravel.md`
- `docs/assertions.md`
- `docs/ci.md`

## Contribution Note
Before opening a PR:

```bash
make qa
```

Principles:
- Keep `packages/core` framework-agnostic.
- Put Laravel ergonomics in `packages/laravel`.
- Update tests and docs with behavior changes.

See `docs/CONTRIBUTING.md`.
