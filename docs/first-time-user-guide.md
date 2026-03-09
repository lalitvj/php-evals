# First-Time User Guide

This guide is for developers using `php-evals` for the first time.

## What `php-evals` is
`php-evals` is a Laravel-first, dataset-driven testing layer for AI behavior, with a framework-agnostic PHP core underneath.

It is not a replacement for PHPUnit/Pest.  
It solves a different problem: measuring AI output quality over time and detecting regressions before release.

## Why use it
Use `php-evals` when you want to:
- prevent prompt/model changes from silently degrading output quality
- compare a new candidate implementation against a baseline
- measure quality with repeatable metrics (pass rate, score, latency, tokens, cost)
- gate CI merges on eval quality thresholds

## Where it fits
Use it first for:
- support/chat assistants
- RAG answer quality checks
- tool-calling agents
- structured output contracts

Strongest fit:
- Laravel apps already using Prism or Laravel AI
- teams shipping prompt/model changes and wanting repeatable regression checks

Keep using PHPUnit/Pest for:
- deterministic business logic
- pure unit/integration tests
- framework behavior

## How it helps productivity
- moves AI QA from manual spot checks to repeatable suite runs
- makes prompt/model upgrades safer through baseline-vs-candidate diffing
- provides run history for debugging and auditability
- supports reproducibility with deterministic mode, cache, and replay
- lets you start locally with lightweight heuristics, then move to OpenAI-backed scoring when you need higher trust

## Step-by-step: first run (framework-agnostic core)
1. Create config file `php-evals.php`:

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

2. Create suite file `storage/ai-evals/refund.jsonl`:

```json
{"id":"refund_001","input":"I was charged twice","expected":{"assertions":[{"type":"contains","value":"Refund"}]}}
```

3. Run eval:

```bash
packages/core/bin/php-evals --suite=refund --store-runs
```

4. Write JSON report for CI/artifacts:

```bash
packages/core/bin/php-evals --suite=refund --format=json --json-report-path=artifacts/evals.json
```

## Step-by-step: first run (Laravel-first DX)
1. Bootstrap scaffold:

```bash
php artisan ai:eval:init
```

This generates:
- `config/ai-evals.php`
- `storage/ai-evals/sample.jsonl`
- `app/AI/FakeEvalModelClient.php`
- `.github/workflows/ai-evals.yml`

Recommended next step after the sample run:
- switch `model_client` to `PhpEvals\Laravel\Integrations\Prism\PrismModelClient::class` or `PhpEvals\Laravel\Integrations\LaravelAI\LaravelAIModelClient::class`
- keep `similarity_scorer` / `judge_client` on `local` for fast feedback, or move to `openai_embeddings` / `openai` for higher-trust scoring

2. Run first suite:

```bash
php artisan ai:eval sample
```

3. Store and compare runs:

```bash
php artisan ai:eval --suite=sample --store-runs --run-id=baseline
php artisan ai:eval --suite=sample --store-runs --run-id=candidate
php artisan ai:eval:compare baseline candidate --fail-threshold=pass_rate_drop:0.02,avg_score_drop:0.05
```

4. Queue bigger suites:

```bash
php artisan ai:eval:queue --suite=sample --chunk-size=25
php artisan ai:eval:progress <run-id>
php artisan ai:eval:queue --resume-run-id=<run-id>
```

## High-value use cases with examples
### 1) Support assistant regression gate
Goal: ensure support answers keep mandatory policy language.

```json
{"id":"support_001","input":"Can I get a refund?","expected":{"assertions":[{"type":"contains","value":"refund"},{"type":"not_contains","value":"guaranteed"}]}}
```

Use when:
- you frequently update prompts/system messages
- you switch model/provider and want quick safety checks

### 2) RAG quality checks
Goal: validate grounding quality using retrieved context.

```json
{"id":"rag_001","input":"What is the refund timeline?","expected":{"assertions":[{"type":"rag_faithfulness","threshold":0.7},{"type":"rag_relevance","reference":"refund timeline","threshold":0.5},{"type":"rag_context_precision","threshold":0.5}]},"metadata":{"context":["Refunds are processed in 3-5 business days.","Returns allowed within 30 days."]}}
```

Use when:
- hallucination risk is high
- retrieval quality changes across releases

### 3) Tool-calling agent checks
Goal: ensure correct tool usage and completion quality.

```json
{"id":"agent_001","input":"Open refund ticket for order A1","expected":{"assertions":[{"type":"tool_call_accuracy","expected":["create_ticket"],"exact":true},{"type":"goal_completion","goal":"Create a refund ticket and confirm next steps","threshold":0.6}]}}
```

Use when:
- workflows depend on calling the right tools
- agent plans change with model upgrades

## Baseline vs candidate workflow (recommended)
1. Capture a known-good run:

```bash
packages/core/bin/php-evals --suite=refund --store-runs --run-id=baseline
```

2. Run candidate version:

```bash
packages/core/bin/php-evals --suite=refund --store-runs --run-id=candidate \
  --compare-baseline-run-id=baseline \
  --compare-candidate-run-id=candidate \
  --fail-threshold=pass_rate_drop:0.02,avg_score_drop:0.05
```

3. Fail CI when thresholds are violated (exit code `1`).

## Deterministic and reproducible runs
Use these together:
- `--deterministic --seed=<int>` for stable request behavior
- `--cache-enabled --cache-path=<path>` to reuse provider responses
- `--replay-run-id=<run_id>` to replay a stored run for debugging

## Scoring tiers
Local / lightweight:
- `similarity_scorer => 'local'`
- `judge_client => 'local'`

OpenAI-backed:
- `similarity_scorer => 'openai_embeddings'`
- `judge_client => 'openai'`

## Run storage options
File store:
- `--run-store-driver=file`
- `--run-store-path=storage/ai-evals/runs`

Database store:
- `--run-store-driver=database`
- `--run-store-dsn=<dsn>`
- `--run-store-user=<user>`
- `--run-store-password=<password>`

Laravel includes migration support for `ai_eval_runs`.

## CI behavior
Exit codes:
- `0`: all assertions and thresholds passed
- `1`: assertion failures or threshold violations
- `2`: configuration/validation/runtime errors

Use `--format=json --json-report-path=...` for CI artifacts and dashboards.

## Next docs to read
- `docs/installation.md`
- `docs/assertions.md`
- `docs/ci.md`
- `docs/quickstart-core.md`
- `docs/quickstart-laravel.md`
