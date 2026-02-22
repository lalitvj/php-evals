# Contributing

## Branching Model
Each implementation ticket is handled in its own branch and merged into `master` after:
1. tests pass
2. static analysis passes
3. formatting checks pass
4. review approval is provided

## Local Commands
Prerequisite: Docker daemon must be running.

- `make build`
- `make install`
- `make test`
- `make analyse`
- `make format-check`
- `make qa`

Use the containerized workflow to ensure consistent PHP 8.2+ behavior even if your host machine runs an older PHP version.

## Ticket Execution Order
See `ORDERED-EXECUTION-PLAN.md`.
