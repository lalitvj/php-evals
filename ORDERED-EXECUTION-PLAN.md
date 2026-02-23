# Ordered Execution Plan (`php-evals` V1)

## Workflow Rules
1. Create one branch per ticket from `master`.
2. Implement only that ticket scope.
3. Add/adjust tests and docs for the ticket.
4. Run quality gates (`tests`, `static analysis`, `style`).
5. Open PR for your review.
6. Merge to `master` only after your approval and green checks.

## Ordered Tickets

| Order | Ticket | Title | Branch | Status |
|---:|---|---|---|---|
| 1 | PE-001 | Monorepo/package scaffold | `lalitvjy/pe-001-monorepo-scaffold` | Implemented (MVP, Ready for Review) |
| 2 | PE-002 | Core package baseline | `lalitvjy/pe-002-core-baseline` | Implemented (MVP, Ready for Review) |
| 3 | PE-003 | Core contracts and DTOs | `lalitvjy/pe-003-core-contracts-dtos` | Implemented (MVP, Ready for Review) |
| 4 | PE-004 | Core configuration model | `lalitvjy/pe-004-core-config-model` | Implemented (MVP, Ready for Review) |
| 5 | PE-005 | JSONL suite loader | `lalitvjy/pe-005-jsonl-suite-loader` | Implemented (MVP, Ready for Review) |
| 6 | PE-006 | Eval case validator | `lalitvjy/pe-006-eval-case-validator` | Implemented (MVP, Ready for Review) |
| 7 | PE-007 | Eval runner engine | `lalitvjy/pe-007-eval-runner-engine` | Implemented (MVP, Ready for Review) |
| 8 | PE-008 | Assertion: contains | `lalitvjy/pe-008-contains-assertion` | Implemented (MVP, Ready for Review) |
| 9 | PE-009 | Assertion: not_contains | `lalitvjy/pe-009-not-contains-assertion` | Implemented (MVP, Ready for Review) |
| 10 | PE-010 | Assertion: regex | `lalitvjy/pe-010-regex-assertion` | Implemented (MVP, Ready for Review) |
| 11 | PE-011 | Assertion: json_schema | `lalitvjy/pe-011-json-schema-assertion` | Implemented (MVP, Ready for Review) |
| 12 | PE-012 | Assertion registry | `lalitvjy/pe-012-assertion-registry` | Implemented (MVP, Ready for Review) |
| 13 | PE-013 | Core CLI binary | `lalitvjy/pe-013-core-cli-binary` | Implemented (MVP, Ready for Review) |
| 14 | PE-014 | Console reporting | `lalitvjy/pe-014-console-reporting` | Implemented (MVP, Ready for Review) |
| 15 | PE-015 | JSON reporting | `lalitvjy/pe-015-json-reporting` | Implemented (MVP, Ready for Review) |
| 16 | PE-016 | Semantic similarity assertion | `lalitvjy/pe-016-semantic-similarity` | Implemented (MVP, Ready for Review) |
| 17 | PE-017 | Function-calling assertions | `lalitvjy/pe-017-function-calling-assertions` | Implemented (MVP, Ready for Review) |
| 18 | PE-018 | Core test helpers | `lalitvjy/pe-018-core-test-helpers` | Implemented (MVP, Ready for Review) |
| 19 | PE-019 | Core feature tests | `lalitvjy/pe-019-core-feature-tests` | Implemented (MVP, Ready for Review) |
| 20 | PE-020 | Core unit tests | `lalitvjy/pe-020-core-unit-tests` | Implemented (MVP, Ready for Review) |
| 21 | PE-021 | Reporting tests | `lalitvjy/pe-021-reporting-tests` | Implemented (MVP, Ready for Review) |
| 22 | PE-022 | Laravel bridge scaffold | `lalitvjy/pe-022-laravel-bridge-scaffold` | Implemented (MVP, Ready for Review) |
| 23 | PE-023 | Artisan wrapper command | `lalitvjy/pe-023-artisan-wrapper` | Implemented (MVP, Ready for Review) |
| 24 | PE-024 | Laravel bridge tests | `lalitvjy/pe-024-laravel-bridge-tests` | Implemented (MVP, Ready for Review) |
| 25 | PE-025 | Documentation set | `lalitvjy/pe-025-documentation-set` | Implemented (MVP, Ready for Review) |
| 26 | PE-026 | Release hardening | `lalitvjy/pe-026-release-hardening` | Implemented (MVP, Ready for Review) |

## Quality Gate Baseline (Per Ticket)
- `composer test`
- `composer analyse`
- `composer format -- --test`

## Environment Setup
- Host runtime can be older (for example `PHP 7.4.x`).
- Use the project containerized environment for all checks:
  1. `make build`
  2. `make install`
  3. `make qa`
