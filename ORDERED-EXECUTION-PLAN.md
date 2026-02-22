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
| 1 | PE-001 | Monorepo/package scaffold | `lalitvjy/pe-001-monorepo-scaffold` | Ready for Review |
| 2 | PE-002 | Core package baseline | `lalitvjy/pe-002-core-baseline` | Not Started |
| 3 | PE-003 | Core contracts and DTOs | `lalitvjy/pe-003-core-contracts-dtos` | Not Started |
| 4 | PE-004 | Core configuration model | `lalitvjy/pe-004-core-config-model` | Not Started |
| 5 | PE-005 | JSONL suite loader | `lalitvjy/pe-005-jsonl-suite-loader` | Not Started |
| 6 | PE-006 | Eval case validator | `lalitvjy/pe-006-eval-case-validator` | Not Started |
| 7 | PE-007 | Eval runner engine | `lalitvjy/pe-007-eval-runner-engine` | Not Started |
| 8 | PE-008 | Assertion: contains | `lalitvjy/pe-008-contains-assertion` | Not Started |
| 9 | PE-009 | Assertion: not_contains | `lalitvjy/pe-009-not-contains-assertion` | Not Started |
| 10 | PE-010 | Assertion: regex | `lalitvjy/pe-010-regex-assertion` | Not Started |
| 11 | PE-011 | Assertion: json_schema | `lalitvjy/pe-011-json-schema-assertion` | Not Started |
| 12 | PE-012 | Assertion registry | `lalitvjy/pe-012-assertion-registry` | Not Started |
| 13 | PE-013 | Core CLI binary | `lalitvjy/pe-013-core-cli-binary` | Not Started |
| 14 | PE-014 | Console reporting | `lalitvjy/pe-014-console-reporting` | Not Started |
| 15 | PE-015 | JSON reporting | `lalitvjy/pe-015-json-reporting` | Not Started |
| 16 | PE-016 | Semantic similarity assertion | `lalitvjy/pe-016-semantic-similarity` | Not Started |
| 17 | PE-017 | Function-calling assertions | `lalitvjy/pe-017-function-calling-assertions` | Not Started |
| 18 | PE-018 | Core test helpers | `lalitvjy/pe-018-core-test-helpers` | Not Started |
| 19 | PE-019 | Core feature tests | `lalitvjy/pe-019-core-feature-tests` | Not Started |
| 20 | PE-020 | Core unit tests | `lalitvjy/pe-020-core-unit-tests` | Not Started |
| 21 | PE-021 | Reporting tests | `lalitvjy/pe-021-reporting-tests` | Not Started |
| 22 | PE-022 | Laravel bridge scaffold | `lalitvjy/pe-022-laravel-bridge-scaffold` | Not Started |
| 23 | PE-023 | Artisan wrapper command | `lalitvjy/pe-023-artisan-wrapper` | Not Started |
| 24 | PE-024 | Laravel bridge tests | `lalitvjy/pe-024-laravel-bridge-tests` | Not Started |
| 25 | PE-025 | Documentation set | `lalitvjy/pe-025-documentation-set` | Not Started |
| 26 | PE-026 | Release hardening | `lalitvjy/pe-026-release-hardening` | Not Started |

## Quality Gate Baseline (Per Ticket)
- `composer test`
- `composer analyse`
- `composer format -- --test`

## Environment Constraint
- Current local runtime is `PHP 7.4.33`.
- Project target is modern PHP (`^8.2`), so full quality gates require PHP 8.2+ runtime.
