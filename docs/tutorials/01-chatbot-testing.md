# Test Your Laravel AI Chatbot in 5 Minutes

## The Problem

You have a Laravel application with an AI-powered chatbot. It works well in manual testing, but you have no way to know when a model upgrade, prompt change, or code refactor causes regressions. A question that used to get a helpful refund answer might suddenly return "I don't know." You need automated, repeatable evaluations.

## What You Will Build

- A JSONL dataset with four customer support scenarios
- Assertions using `contains`, `not_contains`, `regex`, and `semantic_similarity`
- A working eval command that produces a table report
- A stored run you can compare against future changes

## Prerequisites

- A Laravel application (10.x or 11.x)
- PHP 8.2 or later
- Composer
- An OpenAI API key (or any LLM provider)

## Step 1: Install php-evals

```bash
composer require php-evals/core php-evals/laravel
```

The service provider (`PhpEvals\Laravel\LaravelEvalsServiceProvider`) is auto-discovered. If auto-discovery is disabled, register it in `config/app.php`:

```php
'providers' => [
    // ...
    PhpEvals\Laravel\LaravelEvalsServiceProvider::class,
],
```

## Step 2: Scaffold the Eval Directory

```bash
php artisan ai:eval:init
```

This creates four files:

| File | Purpose |
|------|---------|
| `config/ai-evals.php` | Central configuration: model client, dataset path, thresholds |
| `storage/ai-evals/sample.jsonl` | A sample suite to verify the install |
| `app/AI/FakeEvalModelClient.php` | A hardcoded client (no API key needed) |
| `.github/workflows/ai-evals.yml` | CI workflow template |

Verify the scaffolding works:

```bash
php artisan ai:eval sample
```

You should see a table with one passing case.

## Step 3: Configure the Model Client

Copy the OpenAI adapter into your project:

```bash
cp vendor/php-evals/core/docs/examples/adapters/OpenAIModelClient.php app/AI/OpenAIModelClient.php
```

Update `config/ai-evals.php`:

```php
return [
    'dataset_path' => storage_path('ai-evals'),
    'format' => 'table',

    'store_runs' => true,
    'run_store_driver' => 'file',
    'run_store_path' => storage_path('ai-evals/runs'),

    'cache_enabled' => true,
    'cache_path' => storage_path('ai-evals/cache'),

    'model_client' => \App\AI\OpenAIModelClient::class,
    'model_client_options' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model'   => 'gpt-4o',
    ],

    'fail_thresholds' => [
        'pass_rate_drop' => 0.05,
        'avg_score_drop' => 0.05,
    ],
];
```

Add to `.env`:

```
OPENAI_API_KEY=sk-your-key-here
```

Adapters for Anthropic, Gemini, Prism, and Laravel AI SDK are available in `docs/examples/adapters/`.

## Step 4: Create a Test Dataset

Create `storage/ai-evals/support-bot.jsonl` with one JSON object per line:

```jsonl
{"id":"support_001","input":"I was charged twice for my order #12345","expected":{"assertions":[{"type":"contains","value":"refund"},{"type":"not_contains","value":"I don't know"},{"type":"semantic_similarity","reference":"We will process a refund for the duplicate charge","threshold":0.5}]},"metadata":{"goal":"Acknowledge duplicate charge and offer refund"}}
{"id":"support_002","input":"How do I reset my password?","expected":{"assertions":[{"type":"contains","value":"password"},{"type":"regex","pattern":"/reset|change|update/i"},{"type":"semantic_similarity","reference":"Go to Settings, click Security, then Reset Password","threshold":0.4}]},"metadata":{"goal":"Provide clear password reset instructions"}}
{"id":"support_003","input":"I want to cancel my subscription","expected":{"assertions":[{"type":"contains","value":"cancel"},{"type":"not_contains","value":"impossible"},{"type":"semantic_similarity","reference":"You can cancel your subscription from your account settings","threshold":0.4}]},"metadata":{"goal":"Explain cancellation process without friction"}}
{"id":"support_004","input":"My order hasn't arrived and it's been 2 weeks","expected":{"assertions":[{"type":"contains","value":"shipping"},{"type":"regex","pattern":"/track|status|delivery/i"},{"type":"not_contains","value":"your fault"}]},"metadata":{"goal":"Show empathy and provide tracking help"}}
```

### What Each Assertion Does

| Assertion | Meaning |
|-----------|---------|
| `contains` | Output must include the substring (case-insensitive) |
| `not_contains` | Output must NOT include the substring |
| `regex` | Output must match the regular expression |
| `semantic_similarity` | Output must be semantically similar to the reference above the threshold (0.0--1.0) |

A case passes only when all its assertions pass.

## Step 5: Run Evals

```bash
php artisan ai:eval support-bot
```

### Expected Output

```
+-------------+-----------------------------------------------+--------+-------+
| Case        | Input                                         | Status | Score |
+-------------+-----------------------------------------------+--------+-------+
| support_001 | I was charged twice for my order #12345       | PASS   | 1.00  |
| support_002 | How do I reset my password?                   | PASS   | 1.00  |
| support_003 | I want to cancel my subscription              | PASS   | 1.00  |
| support_004 | My order hasn't arrived and it's been 2 weeks | FAIL   | 0.67  |
+-------------+-----------------------------------------------+--------+-------+

Suite: support-bot | Cases: 4 | Passed: 3 | Failed: 1 | Pass rate: 75.0%
```

If a case fails:

- **`contains` failing**: The model phrased it differently. Consider `regex` with alternates or `semantic_similarity`.
- **`semantic_similarity` failing**: Lower the threshold or broaden the reference text.
- **`not_contains` failing**: The model included a prohibited phrase. Adjust your system prompt.

## Step 6: Store Runs for Tracking

Create a named run you can compare against later:

```bash
php artisan ai:eval support-bot --store-runs --run-id=v1
```

After making a change (prompt update, model swap), run again:

```bash
php artisan ai:eval support-bot --store-runs --run-id=v2
```

Compare the two:

```bash
php artisan ai:eval:compare v1 v2
```

The comparison shows pass rate change, average score delta, and per-case regressions. If the pass rate drops beyond the configured threshold, the command exits with code `1`.

For machine-readable output:

```bash
php artisan ai:eval support-bot --format=json --json-report-path=artifacts/evals.json
```

## Next Steps

- **Evaluate your RAG pipeline**: See [Tutorial 02: RAG Evaluation](./02-rag-evaluation.md)
- **Test tool calling**: See [Tutorial 03: Tool Calling](./03-tool-calling.md)
- **Add evals to CI**: See [Tutorial 04: CI/CD Regression](./04-ci-cd-regression.md)
- **All 14 assertion types**: See `docs/assertions.md`
