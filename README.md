# php-evals

[![Tests](https://github.com/lalitvj/php-evals/actions/workflows/ai-evals.yml/badge.svg)](https://github.com/lalitvj/php-evals/actions)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-8892BF.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**The evaluation framework for PHP LLM applications.** Test your chatbots, RAG pipelines, AI agents, and structured outputs with 14 assertion types, baseline/candidate comparison, queue support, and CI integration.

> Think **DeepEval for PHP**. Works alongside Prism, Laravel AI SDK, or any HTTP-based LLM provider.

## 30-Second Quickstart

```bash
# Install
composer require php-evals/core php-evals/laravel

# Scaffold config, sample suite, and CI workflow
php artisan ai:eval:init

# Run the sample suite
php artisan ai:eval sample

# Run your own suite
php artisan ai:eval support-bot --store-runs --run-id=v1
```

That's it. You have a stored eval run with per-case scores, latency, tokens, and cost.

## Why php-evals?

PHPUnit tests deterministic logic. But LLM outputs are non-deterministic -- the same prompt can return different wording every time. You need a different kind of testing.

| | Manual Testing | PHPUnit / Pest | php-evals |
|---|---|---|---|
| **What it tests** | "Does this look right?" | Deterministic business logic | LLM output quality and behavior |
| **Speed** | Hours per review cycle | Seconds | Minutes (parallelizable via queues) |
| **Consistency** | Varies by reviewer | Deterministic | Same assertions every run |
| **Scalability** | 10-20 cases max | Unlimited | 500+ cases via queue workers |
| **Regression detection** | None | Assert exact values | Baseline vs candidate with thresholds |
| **CI integration** | None | Exit codes | Exit codes + JSON reports + comparison gates |
| **History** | Spreadsheet notes | Pass/fail | Full run history with metrics and diffs |

**php-evals complements PHPUnit/Pest.** Use tests for your application logic. Use evals for your AI output quality.

## How php-evals Fits the Laravel AI Ecosystem

| Tool | What It Does | Relationship to php-evals |
|------|-------------|--------------------------|
| **[Prism](https://github.com/echolabsdev/prism)** | Call LLMs via a unified API | Use `PrismModelClient` adapter to eval your Prism app |
| **[Laravel AI SDK](https://github.com/laravel/ai)** | Build AI agents with tools | Use `LaravelAIModelClient` adapter to eval your agents |
| **Direct API calls** | Raw HTTP to OpenAI / Anthropic / Gemini | Use the provider-specific adapters |
| **PHPUnit / Pest** | Test deterministic app logic | Keep using them. php-evals handles the non-deterministic AI layer |

**They call LLMs. php-evals tests them.** They are complementary, not competing.

## 14 Assertion Types

### String and structure checks
- `contains` -- output includes a substring
- `not_contains` -- output excludes a substring
- `regex` -- output matches a pattern
- `json_schema` -- output validates against a JSON Schema

### Semantic checks
- `semantic_similarity` -- output is semantically similar to a reference

### RAG metrics
- `rag_faithfulness` -- answer is grounded in retrieved context
- `rag_relevance` -- answer addresses the question
- `rag_context_precision` -- retrieved context is precise (low noise)

### Tool / agent assertions
- `tool_called` -- a specific tool was invoked
- `tool_call_count` -- correct number of tool calls (min/max/exact)
- `tool_args_schema` -- tool arguments match a JSON Schema
- `tool_call_accuracy` -- exact or subset match on expected tool set
- `goal_completion` -- overall task success assessment

### LLM-as-judge
- `llm_judge_rubric` -- another LLM scores the response against a rubric

## Model Adapters

Copy-pasteable adapters for every major provider are in [`docs/examples/adapters/`](docs/examples/adapters/):

| Adapter | Provider |
|---------|----------|
| `OpenAIModelClient` | OpenAI (GPT-4o, GPT-4o-mini, etc.) |
| `AnthropicModelClient` | Anthropic (Claude Sonnet, Opus, Haiku) |
| `GeminiModelClient` | Google Gemini |
| `PrismModelClient` | Any provider via Prism |
| `LaravelAIModelClient` | Any provider via Laravel AI SDK |
| `OpenAIJudgeClient` | LLM-as-judge via OpenAI |

Each adapter implements one interface: `ModelClient::complete(ModelRequest): ModelResponse`.

## Key Features

### Baseline vs Candidate Comparison

```bash
php artisan ai:eval support-bot --store-runs --run-id=baseline
# ... make a change ...
php artisan ai:eval support-bot --store-runs --run-id=candidate
php artisan ai:eval:compare baseline candidate \
    --fail-threshold=pass_rate_drop:0.02,avg_score_drop:0.05
```

Exit code `1` if quality drops beyond your threshold. Gate your CI on it.

### Queue Support for Large Suites

```bash
php artisan ai:eval:queue --suite=support-bot --chunk-size=25 --run-id=queued-v1
php artisan ai:eval:progress queued-v1
php artisan ai:eval:queue --resume-run-id=queued-v1  # resume on failure
```

Splits suites into chunked queue jobs. Parallel execution across workers. Resume from failure without re-running completed chunks.

### CI Quality Gates

```yaml
# .github/workflows/ai-evals.yml
- run: php artisan ai:eval support-bot --store-runs --run-id=candidate --format=json --json-report-path=artifacts/report.json
- run: php artisan ai:eval:compare baseline candidate --fail-threshold=pass_rate_drop:0.02,avg_score_drop:0.05
```

Exit codes: `0` = pass, `1` = regression detected, `2` = config error.

### Deterministic Mode + Replay

```bash
php artisan ai:eval support-bot --deterministic --seed=42 --cache-enabled --cache-ttl=3600
php artisan ai:eval support-bot --replay-run-id=v1  # reuse stored responses
```

### Run Storage

- **File store**: `--run-store-driver=file --run-store-path=storage/ai-evals/runs`
- **Database store**: `--run-store-driver=database --run-store-dsn=...`
- Laravel includes migration support for `ai_eval_runs`.

## Getting Started

### Requirements
- PHP `^8.2`
- Composer
- Docker (recommended for local development)

### Laravel (Recommended)

```bash
composer require php-evals/core php-evals/laravel
php artisan ai:eval:init
```

This creates `config/ai-evals.php`, a sample suite, a fake model client, and a CI workflow. Configure your real model client and run:

```bash
php artisan ai:eval sample
```

### Framework-Agnostic Core

```bash
composer require php-evals/core
```

Create `php-evals.php` in your project root:

```php
<?php

declare(strict_types=1);

return [
    'dataset_path' => __DIR__.'/storage/ai-evals',
    'store_runs' => true,
    'run_store_driver' => 'file',
    'run_store_path' => __DIR__.'/storage/ai-evals/runs',
    'model_client' => \App\AI\OpenAIModelClient::class,
    'model_client_options' => [
        'api_key' => getenv('OPENAI_API_KEY'),
        'model' => 'gpt-4o',
    ],
];
```

Create a suite file `storage/ai-evals/support-bot.jsonl`:

```json
{"id":"support_001","input":"I was charged twice for order #12345","expected":{"assertions":[{"type":"contains","value":"refund"},{"type":"not_contains","value":"I don't know"}]}}
```

Run:

```bash
vendor/bin/php-evals --suite=support-bot --store-runs
```

## Example Datasets

Ready-to-use JSONL datasets in [`docs/examples/datasets/`](docs/examples/datasets/):

| Dataset | Cases | Assertions Covered |
|---------|-------|--------------------|
| `support-bot.jsonl` | 10 | contains, not_contains, regex, semantic_similarity |
| `rag-quality.jsonl` | 8 | rag_faithfulness, rag_relevance, rag_context_precision |
| `agent-tools.jsonl` | 6 | tool_called, tool_call_count, tool_args_schema, tool_call_accuracy |
| `structured-output.jsonl` | 5 | json_schema, contains |
| `judge-rubric.jsonl` | 4 | llm_judge_rubric, goal_completion |

## Tutorials

Step-by-step guides in [`docs/tutorials/`](docs/tutorials/):

1. [Test Your Laravel AI Chatbot in 5 Minutes](docs/tutorials/01-chatbot-testing.md)
2. [Evaluate Your RAG Pipeline](docs/tutorials/02-rag-evaluation.md)
3. [Test Your AI Agent's Tool Calling](docs/tutorials/03-tool-calling.md)
4. [CI/CD for AI: Catch Regressions Before They Ship](docs/tutorials/04-ci-cd-regression.md)
5. [Scale Evals with Laravel Queues](docs/tutorials/05-queue-at-scale.md)
6. [Structured Output Validation](docs/tutorials/06-structured-output.md)
7. [LLM-as-Judge Evaluation](docs/tutorials/07-llm-as-judge.md)
8. [Migrate from Manual to Automated Evals](docs/tutorials/08-manual-to-automated.md)

## Documentation

- [First-Time User Guide](docs/first-time-user-guide.md) -- start here
- [Installation](docs/installation.md)
- [Quickstart (Core)](docs/quickstart-core.md)
- [Quickstart (Laravel)](docs/quickstart-laravel.md)
- [Assertions Reference](docs/assertions.md)
- [CI Guide](docs/ci.md)
- [Extending php-evals](docs/extending.md)

## Contributing

```bash
make qa
```

Principles:
- Keep `packages/core` framework-agnostic.
- Put Laravel ergonomics in `packages/laravel`.
- Update tests and docs with behavior changes.

See [CONTRIBUTING.md](docs/CONTRIBUTING.md).

## License

MIT
