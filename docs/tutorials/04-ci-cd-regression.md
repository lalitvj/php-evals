# CI/CD for AI: Catch Regressions Before They Ship

## The Problem

LLM updates, prompt changes, or model swaps can silently degrade quality. A chatbot that scored 95% pass rate last week might drop to 80% after a seemingly harmless prompt tweak. Without automated regression detection, these drops ship to production unnoticed.

## The Solution

Run baseline vs candidate comparisons in CI. If quality drops beyond your threshold, the pipeline fails.

## Step 1: Create a Baseline

Run your eval suite and store the results with a named ID:

```bash
php artisan ai:eval support-bot --store-runs --run-id=baseline-v1
```

This persists per-case scores, latency, tokens, and cost to `storage/ai-evals/runs/`.

## Step 2: Make a Change

Update your system prompt, swap the model, or change your retrieval pipeline. Whatever change you want to validate.

## Step 3: Run a Candidate

```bash
php artisan ai:eval support-bot --store-runs --run-id=candidate-v1
```

## Step 4: Compare Runs

```bash
php artisan ai:eval:compare baseline-v1 candidate-v1 \
    --fail-threshold=pass_rate_drop:0.02,avg_score_drop:0.05
```

### Understanding Thresholds

| Threshold | Meaning |
|-----------|---------|
| `pass_rate_drop:0.02` | Fail if pass rate drops by more than 2 percentage points |
| `avg_score_drop:0.05` | Fail if average score drops by more than 0.05 |

If baseline has 90% pass rate and candidate has 87%, that is a 3% drop -- exceeds the 2% threshold, so the compare command exits with code `1`.

## Step 5: GitHub Actions Integration

```yaml
name: ai-evals

on:
  pull_request:
  push:
    branches: [main]

jobs:
  evals:
    runs-on: ubuntu-latest
    env:
      OPENAI_API_KEY: ${{ secrets.OPENAI_API_KEY }}
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - run: composer install --no-interaction --prefer-dist

      # Run the eval suite
      - name: Run evals
        run: |
          php artisan ai:eval support-bot \
            --store-runs \
            --run-id=candidate \
            --format=json \
            --json-report-path=artifacts/report.json

      # Compare against baseline
      - name: Compare against baseline
        run: |
          php artisan ai:eval:compare baseline candidate \
            --fail-threshold=pass_rate_drop:0.02,avg_score_drop:0.05

      # Upload report even if comparison fails
      - uses: actions/upload-artifact@v4
        if: always()
        with:
          name: eval-reports
          path: artifacts/*.json
```

### Exit Codes

| Code | Meaning |
|------|---------|
| `0` | All assertions and thresholds passed |
| `1` | Assertion failures or threshold violations |
| `2` | Configuration or runtime error |

## Managing the Baseline

### Option A: Committed Baseline Run

Store the baseline run output in version control:

```bash
php artisan ai:eval support-bot --store-runs --run-id=baseline
git add storage/ai-evals/runs/baseline.json
git commit -m "Update eval baseline"
```

CI always compares against this committed baseline.

### Option B: Per-Branch Baseline

Generate a fresh baseline from the main branch in CI, then compare against it:

```yaml
- name: Run baseline (main branch)
  run: |
    git stash
    git checkout main
    composer install --no-interaction
    php artisan ai:eval support-bot --store-runs --run-id=baseline --cache-enabled
    git checkout -
    git stash pop
```

### Option C: Named Release Baselines

Tag baselines with release versions:

```bash
php artisan ai:eval support-bot --store-runs --run-id=baseline-v2.1
```

Compare PRs against the latest release baseline.

## Cost Reduction in CI

Enable caching to avoid paying for identical inputs across runs:

```bash
php artisan ai:eval support-bot \
    --store-runs \
    --run-id=candidate \
    --cache-enabled \
    --cache-ttl=86400
```

Use deterministic mode for consistent outputs:

```bash
php artisan ai:eval support-bot \
    --store-runs \
    --run-id=candidate \
    --deterministic \
    --seed=42
```

## Best Practices

1. **Keep baselines up to date.** After an intentional quality improvement, update the baseline so future comparisons reflect the new standard.
2. **Use tight thresholds for critical suites.** A customer-facing chatbot should tolerate at most 2% pass rate drop. Internal tools can be more lenient.
3. **Cache aggressively in CI.** Set `--cache-enabled --cache-ttl=86400` to reuse responses for 24 hours.
4. **Save JSON reports.** Use `--json-report-path` and upload as build artifacts for debugging failed pipelines.
5. **Start with a smoke suite.** Run your 10-20 most critical cases in CI, not the full 500-case suite. Scale with queues when needed.

## Next Steps

- **Scale evals with queues**: See [Tutorial 05: Queue at Scale](./05-queue-at-scale.md)
- **LLM-as-judge for subjective quality**: See [Tutorial 07: LLM-as-Judge](./07-llm-as-judge.md)
- **Full CI reference**: See `docs/ci.md`
