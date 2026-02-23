# Quickstart (Core)

For a full first-run walkthrough with examples and use cases, start here:

- `docs/first-time-user-guide.md`

Core-only shortest path:
1. Install dependencies (`make build && make install`).
2. Create `php-evals.php` with `dataset_path` and `model_client`.
3. Add suite file in JSONL format under your dataset path.
4. Run `packages/core/bin/php-evals --suite=<suite-name> --store-runs`.
5. Optionally export JSON report with `--format=json --json-report-path=...`.

Exit codes:
- `0`: all assertions and thresholds passed
- `1`: assertion failures or threshold violations
- `2`: configuration/runtime/validation errors
