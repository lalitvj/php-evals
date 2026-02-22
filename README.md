# php-evals

Framework-agnostic PHP evaluation toolkit for LLM outputs, with Laravel-style developer ergonomics.

## Local Dev Environment (PHP 8.3)
This repository includes a containerized environment so host PHP version does not block development.

Prerequisites:
- Docker Desktop (or Docker daemon) running locally

1. Build image: `make build`
2. Install dependencies: `make install`
3. Run tests: `make test`
4. Run static analysis: `make analyse`
5. Verify formatting: `make format-check`
6. Run all quality checks: `make qa`

## Repository Layout
- `packages/core`: framework-agnostic evaluation engine (CLI + contracts + assertions).
- `packages/laravel`: optional Laravel bridge for Service Provider + Artisan command.
- `docs`: project and contributor documentation.

## Local Quality Commands
- `make qa`
- `make test`
- `make analyse`
- `make format-check`

## Branch Workflow
Each ticket is implemented in its own branch and merged into `master` only after review and green quality checks.
