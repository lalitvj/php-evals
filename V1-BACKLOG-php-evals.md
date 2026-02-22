# `php-evals` V1 Implementation Backlog

## Planning Assumptions
- Effort unit: engineer-days (single engineer).
- Dependency format: ticket IDs that must be completed first.
- Priority: `P0` critical path, `P1` important, `P2` stretch.
- Architecture baseline: framework-agnostic core + optional Laravel bridge package.

## Milestone Map
- Milestone 1: Core foundation and contracts.
- Milestone 2: Core eval engine and base assertions.
- Milestone 3: function-calling eval + reporting.
- Milestone 4: Laravel bridge + hardening + release.

## Ticket Backlog

| ID | Title | Scope / Deliverable | Priority | Estimate (days) | Dependencies |
|---|---|---|---|---:|---|
| PE-001 | Monorepo/package scaffold | Set up `packages/core` and `packages/laravel`, root QA tooling, CI skeleton. | P0 | 1.5 | - |
| PE-002 | Core package baseline | `packages/core/composer.json`, autoloading, strict types, base namespaces. | P0 | 0.75 | PE-001 |
| PE-003 | Core contracts and DTOs | `ModelClient`, `Assertion`, `SimilarityScorer`, DTOs including `ModelResponse` and `ToolCall`. | P0 | 1.25 | PE-002 |
| PE-004 | Core configuration model | Framework-agnostic config options object + optional file loader. | P0 | 0.75 | PE-002, PE-003 |
| PE-005 | JSONL suite loader | Suite discovery and JSONL parser for configurable paths. | P0 | 1.0 | PE-004 |
| PE-006 | Eval case validator | Row-level validation and typed exceptions for malformed cases. | P0 | 1.0 | PE-005 |
| PE-007 | Eval runner engine | Case execution, assertion dispatch, model invocation, aggregate results. | P0 | 2.0 | PE-003, PE-006 |
| PE-008 | Assertion: contains | Implement `contains` with actionable failure details. | P0 | 0.5 | PE-003 |
| PE-009 | Assertion: not_contains | Implement `not_contains` with concise diagnostics. | P0 | 0.5 | PE-003 |
| PE-010 | Assertion: regex | Implement `regex` and invalid pattern handling. | P1 | 0.5 | PE-003 |
| PE-011 | Assertion: json_schema | JSON parsing + schema assertion for structured responses. | P1 | 1.0 | PE-003 |
| PE-012 | Assertion registry | Register and resolve assertion implementations by `type`. | P0 | 0.75 | PE-007, PE-008, PE-009, PE-010, PE-011 |
| PE-013 | Core CLI binary | `vendor/bin/php-evals` command + flags + standard exit codes. | P0 | 1.5 | PE-007, PE-012 |
| PE-014 | Console reporting | Human-readable summary and case-level failure output. | P1 | 0.75 | PE-013 |
| PE-015 | JSON reporting | Machine-readable output for CI artifacts/parsing. | P1 | 0.75 | PE-013 |
| PE-016 | Semantic similarity assertion | Contract-based `semantic_similarity` assertion with deterministic default scorer. | P1 | 1.0 | PE-003, PE-012 |
| PE-017 | Function-calling assertions | Add `tool_called`, `tool_call_count`, and `tool_args_schema`. | P0 | 1.5 | PE-003, PE-012 |
| PE-018 | Core test helpers | Utilities for Pest/PHPUnit to execute eval suites in any PHP app. | P2 | 0.75 | PE-013 |
| PE-019 | Core feature tests | CLI flags, pass/fail paths, stop-on-failure, exit code behavior. | P0 | 1.5 | PE-013, PE-017 |
| PE-020 | Core unit tests | Loader/validator/assertions/unit coverage including function-call assertions. | P0 | 1.75 | PE-005, PE-006, PE-008, PE-009, PE-010, PE-011, PE-016, PE-017 |
| PE-021 | Reporting tests | Console and JSON report contract tests/snapshots. | P1 | 0.75 | PE-014, PE-015 |
| PE-022 | Laravel bridge scaffold | `packages/laravel` service provider, package config, composer constraints. | P0 | 1.0 | PE-001, PE-013 |
| PE-023 | Artisan wrapper command | `php artisan ai:eval` wrapper backed by core runner and config mapping. | P0 | 1.0 | PE-022 |
| PE-024 | Laravel bridge tests | Testbench tests for command wiring and config behavior. | P1 | 1.0 | PE-023 |
| PE-025 | Documentation set | Install + quickstart (core/Laravel), assertions, function calling, CI, extension docs. | P0 | 1.75 | PE-013, PE-015, PE-017, PE-023 |
| PE-026 | Release hardening | CI matrix, QA gates, changelog starter, release checklist. | P0 | 1.0 | PE-019, PE-020, PE-021, PE-024, PE-025 |

## Estimated Total
- Total estimated effort: **26.0 engineer-days**
- Practical delivery window: **4-5 weeks** (single engineer, includes feedback and rework).

## Critical Path
1. PE-001 -> PE-002 -> PE-003 -> PE-004
2. PE-005 -> PE-006 -> PE-007
3. PE-008/PE-009/PE-010/PE-011 -> PE-012
4. PE-013 -> PE-017
5. PE-019 + PE-020
6. PE-022 -> PE-023 -> PE-024
7. PE-025 -> PE-026

## Suggested Sprint Breakdown

### Sprint 1 (Week 1): Core Foundation
- PE-001, PE-002, PE-003, PE-004, PE-005, PE-006
- Outcome: framework-agnostic foundation with validated datasets.

### Sprint 2 (Week 2): Core Execution
- PE-007, PE-008, PE-009, PE-010, PE-011, PE-012, PE-013
- Outcome: end-to-end core CLI execution with base assertions.

### Sprint 3 (Week 3): Function Calling + Reporting
- PE-014, PE-015, PE-016, PE-017, PE-018
- Outcome: CI-grade reports and function-calling evaluation support.

### Sprint 4 (Week 4): Tests + Laravel Bridge
- PE-019, PE-020, PE-021, PE-022, PE-023, PE-024
- Outcome: stable core plus Laravel integration aligned with Laravel DX.

### Sprint 5 (Week 5): Docs + Release
- PE-025, PE-026
- Outcome: contributor-ready docs and release-ready quality gates.

## Dependency Notes
- Select JSON schema library early (PE-011) to avoid refactors.
- Keep function-call DTO design stable before finalizing assertions (PE-017).
- Laravel bridge should remain thin; no domain logic duplicated from core.

## Definition of Done (Per Ticket)
1. Code merged with tests and static analysis passing.
2. Public API/config updates documented.
3. Errors are actionable and concise.
4. No unresolved TODOs in released paths.
