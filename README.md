# php-evals

Framework-agnostic PHP evaluation toolkit for LLM outputs, with Laravel-style developer ergonomics.

## Repository Layout
- `packages/core`: framework-agnostic evaluation engine (CLI + contracts + assertions).
- `packages/laravel`: optional Laravel bridge for Service Provider + Artisan command.
- `docs`: project and contributor documentation.

## Local Quality Commands
- `composer test`
- `composer analyse`
- `composer format -- --test`

## Branch Workflow
Each ticket is implemented in its own branch and merged into `master` only after review and green quality checks.
