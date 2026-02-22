# Product Requirements Document (PRD)

## Project
- Name: `php-evals`
- Version: `v1.0.0`
- Type: Framework-agnostic PHP package with optional Laravel bridge.
- Goal: Bring Python-style AI/ML evaluation discipline to any PHP app, with Laravel-like DX and conventions.

## Background and Inspiration
- Laravel inspiration:
  - Clean APIs, excellent DX, readable conventions, and practical CLI workflow.
  - Familiar command signatures and clear error output.
- Spatie inspiration:
  - Small focused scope, maintainable structure, strong typing, and high package quality.
  - Excellent documentation and test discipline.

## Problem Statement
PHP teams shipping AI features often rely on manual checks and unstable heuristics. Prompt/model/provider changes can silently degrade behavior. Teams need a framework-agnostic way to define eval datasets, assert outputs (including function calling), and fail CI on regressions.

## Goals
1. Support all PHP applications (framework and non-framework).
2. Keep the core package framework-agnostic and dependency-light.
3. Provide Laravel-style developer ergonomics in API and command UX.
4. Include first-class function-calling evaluation support.
5. Keep v1 small, reliable, and OSS-friendly.

## Non-Goals (v1)
1. Full agent orchestration platform.
2. Hosted observability/dashboard product.
3. Full prompt management/versioning system.
4. Official adapters for every provider.

## Target Users
1. Teams building AI features in plain PHP, Laravel, Symfony, Slim, or custom stacks.
2. OSS maintainers who want regression checks for LLM behavior.
3. Engineering teams adopting CI quality gates for AI output.

## Success Metrics (First 90 Days)
1. At least 5 projects adopt package in CI (across at least 2 different PHP stacks).
2. Setup time under 30 minutes for a new project.
3. At least 80% coverage in core package.
4. Active community signals (issues/PRs/discussions).

## Scope: V1 Functional Requirements

### FR-1: Evaluation Dataset Format
1. Support JSONL dataset files by default (configurable path).
2. Each case includes:
   - `id`
   - `input`
   - `expected` (assertion payload)
   - `metadata` (optional)
3. Invalid rows return actionable validation errors with row numbers.

### FR-2: Framework-Agnostic CLI Runner
1. Provide `vendor/bin/php-evals`.
2. Flags:
   - `--suite=` run a specific suite.
   - `--model=` override configured model key.
   - `--format=table|json`.
   - `--stop-on-failure`.
3. Exit codes:
   - `0` all passed.
   - `1` assertion failures.
   - `2` execution/config/runtime errors.

### FR-3: Optional Laravel Bridge
1. Provide optional package integration for Laravel service provider and Artisan command.
2. Support `php artisan ai:eval` as wrapper over core runner.
3. Keep Laravel dependencies outside core package.

### FR-4: Assertions (Core Set)
1. `contains`
2. `not_contains`
3. `regex`
4. `json_schema`
5. `semantic_similarity` (through pluggable scorer contract)

### FR-5: Function Calling Evaluation
1. Support model outputs that include structured function/tool calls.
2. Add assertions:
   - `tool_called` (specific tool/function was invoked)
   - `tool_call_count` (count constraints)
   - `tool_args_schema` (arguments match JSON schema)
3. Assertion failures should print concise mismatch detail.

### FR-6: Provider and Model Abstraction
1. Define `ModelClient` contract with framework-agnostic request/response DTOs.
2. Response DTO must support both text output and optional tool-call trace.
3. Providers remain app-defined; package supplies interfaces and examples.
4. Timeout/retry controls configurable without framework lock-in.

### FR-7: Scoring and Reporting
1. Per-suite summary: totals, pass/fail, duration, assertion stats.
2. JSON report output for CI artifacts.
3. Failed case IDs and reasons printed with actionable hints.

### FR-8: Configuration
1. Core config through constructor/options array and optional PHP config file.
2. Laravel bridge can map these options from Laravel config/publish flow.

### FR-9: Testing Utilities
1. Utilities to run eval suites from Pest/PHPUnit in any PHP project.
2. Laravel-specific docs/examples via bridge package docs.

## Non-Functional Requirements
1. PHP support: `^8.2`.
2. Framework support: agnostic core, no hard framework dependency.
3. Performance: 100 stubbed eval cases should execute quickly.
4. Reliability: deterministic behavior for non-semantic assertions.
5. Extensibility: contracts for assertions, clients, scorers, reporters.
6. Developer Experience: Laravel-like naming, clear errors, concise commands.

## Proposed Repository Structure

```text
php-evals/
  packages/
    core/
      src/
        Commands/
          RunEvalsCommand.php
        Contracts/
          ModelClient.php
          Assertion.php
          SimilarityScorer.php
        Data/
          EvalCase.php
          ModelResponse.php
          ToolCall.php
          EvalResult.php
          SuiteResult.php
        Assertions/
          ContainsAssertion.php
          NotContainsAssertion.php
          RegexAssertion.php
          JsonSchemaAssertion.php
          SemanticSimilarityAssertion.php
          ToolCalledAssertion.php
          ToolCallCountAssertion.php
          ToolArgsSchemaAssertion.php
        Engine/
          EvalRunner.php
          SuiteLoader.php
          CaseValidator.php
        Reporting/
          ConsoleReporter.php
          JsonReporter.php
        Exceptions/
          InvalidEvalCaseException.php
          EvalExecutionException.php
      bin/
        php-evals
      tests/
      composer.json
    laravel/
      src/
        LaravelEvalsServiceProvider.php
        Commands/
          RunEvalsArtisanCommand.php
      config/
        ai-evals.php
      tests/
      composer.json
  docs/
    installation.md
    quickstart-core.md
    quickstart-laravel.md
    assertions.md
    function-calling.md
    ci.md
    extending.md
  .github/workflows/
    tests.yml
  composer.json
  phpstan.neon.dist
  phpunit.xml.dist
  pint.json
  README.md
```

## Code Style and Engineering Standards
1. `declare(strict_types=1);` in all PHP files.
2. Typed properties and explicit return types.
3. Clear contracts and small classes.
4. Laravel-like naming and command ergonomics.
5. Laravel Pint for style normalization.
6. PHPStan strict configuration (target level 8+).
7. Tests:
   - Core tests independent of framework.
   - Laravel bridge tests via Orchestra Testbench.
8. SemVer with changelog discipline.

## Developer Workflows
1. Plain PHP / Symfony / custom app:
   - Install core package.
   - Configure model client + suite path.
   - Run `vendor/bin/php-evals`.
2. Laravel app:
   - Install core + Laravel bridge package.
   - Optional config publish.
   - Run `php artisan ai:eval`.

## Example Eval Case (JSONL)
```json
{"id":"refund_route_001","input":"I was charged twice. What should I do?","expected":{"assertions":[{"type":"contains","value":"refund"},{"type":"tool_called","name":"create_support_ticket"},{"type":"tool_args_schema","name":"create_support_ticket","schema":{"type":"object","required":["order_id"]}}]}}
```

## Milestones

### Milestone 1: Core Foundation (Week 1)
1. Monorepo/package skeleton for `core` and `laravel`.
2. Core contracts + DTOs + config mechanism.
3. Base CLI command wiring.

### Milestone 2: Core Eval Engine (Week 2)
1. Loader + validator + runner.
2. Core assertions (`contains`, `not_contains`, `regex`, `json_schema`).
3. Console output + exit code behavior.

### Milestone 3: Function Calling and Reporting (Week 3)
1. Tool/function-call assertions.
2. `semantic_similarity` contract integration.
3. JSON reporting for CI.

### Milestone 4: Laravel Bridge + Hardening (Week 4)
1. Laravel bridge package and Artisan wrapper.
2. Test coverage and static analysis hardening.
3. Release docs, changelog, and v1 checklist.

## Acceptance Criteria for V1 Release
1. Core package runs end-to-end in non-Laravel PHP project.
2. Laravel bridge provides `php artisan ai:eval`.
3. Function-calling assertions work as documented.
4. CI fails correctly on assertion/threshold failures.
5. Documentation covers both core and Laravel flows.
6. Tests and static analysis pass across supported matrix.

## Risks and Mitigations
1. Model non-determinism:
   - Mitigation: deterministic assertions by default, clear thresholding, stub-first testing examples.
2. Provider diversity:
   - Mitigation: strict contracts, adapter examples, minimal assumptions.
3. Framework drift:
   - Mitigation: keep core framework-free; isolate integration in bridge package.

## Out of Scope Ideas for V2+
1. Prompt registry/versioning module.
2. Built-in provider SDK adapters.
3. Historical trend dashboard.
4. Trace-based dataset generation.

## Open Questions
1. Should Symfony Console be the direct CLI dependency or should CLI be optional?
2. Should function-call assertion payload support partial schema matching in v1?
3. Should Laravel bridge live in same repository from day one or as a follow-up package?
