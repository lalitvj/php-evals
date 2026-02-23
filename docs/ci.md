# CI Guide

`php-evals` supports CI-friendly exit codes:
- `0`: all assertions passed
- `1`: assertion failures
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
      - run: packages/core/bin/php-evals --format=json --json-report-path=artifacts/evals.json
      - uses: actions/upload-artifact@v4
        if: always()
        with:
          name: eval-report
          path: artifacts/evals.json
```

## Repository QA
Use the existing project gates:

```bash
make qa
```
