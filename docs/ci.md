# CI Guide

`php-evals` supports CI-friendly exit codes:
- `0`: all assertions and thresholds passed
- `1`: assertion failures or threshold violations
- `2`: runtime/configuration/validation errors

## GitHub Actions Example

```yaml
name: ai-evals

on:
  pull_request:
  push:
    branches: [master]

jobs:
  evals:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install --no-interaction --prefer-dist --no-progress
      - run: packages/core/bin/php-evals --suite=smoke --store-runs --run-id=baseline --format=json --json-report-path=artifacts/baseline.json
      - run: packages/core/bin/php-evals --suite=smoke --store-runs --run-id=candidate --compare-baseline-run-id=baseline --compare-candidate-run-id=candidate --fail-threshold=pass_rate_drop:0.02,avg_score_drop:0.05 --format=json --json-report-path=artifacts/candidate.json
      - uses: actions/upload-artifact@v4
        if: always()
        with:
          name: eval-reports
          path: artifacts/*.json
```

## Repository QA
Use the existing project gates:

```bash
make qa
```
