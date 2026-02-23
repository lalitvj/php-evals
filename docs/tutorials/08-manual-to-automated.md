# Migrate from Manual to Automated Evals

## The Problem

You are testing your LLM outputs manually: copy-pasting responses into a spreadsheet, eyeballing quality, and hoping nothing regressed. This works for 10 cases. It does not work for 200 cases before every deploy.

Manual testing is:
- **Slow**: Hours per review cycle
- **Inconsistent**: Different reviewers apply different standards
- **Not scalable**: Cannot run on every PR
- **Not auditable**: No history of what was tested and when

## The Journey

```
Manual spot checks  -->  Structured JSONL dataset  -->  Automated pipeline
```

## Step 1: Audit Your Current Testing

Before writing any assertions, document what you already check manually:

| What I Check | Example | How I Check |
|-------------|---------|-------------|
| Mentions the refund policy | "We offer refunds within 30 days" | Read the response |
| Does not say "I don't know" | Absence of the phrase | Scan for red flags |
| Tone is professional | No slang, no rudeness | Subjective judgment |
| JSON output is valid | Has all required fields | Paste into a validator |
| Agent called the right tool | Used `create_refund` not `delete_order` | Check logs |

This inventory becomes your assertion mapping.

## Step 2: Convert Manual Checks to Assertions

| Manual Check | Assertion Type | Example |
|-------------|---------------|---------|
| "Response mentions refund" | `contains` | `{"type": "contains", "value": "refund"}` |
| "Does not say I don't know" | `not_contains` | `{"type": "not_contains", "value": "I don't know"}` |
| "Mentions reset or change" | `regex` | `{"type": "regex", "pattern": "/reset\|change/i"}` |
| "Tone is professional" | `llm_judge_rubric` | `{"type": "llm_judge_rubric", "rubric": "Rate professionalism...", "threshold": 0.7}` |
| "JSON has required fields" | `json_schema` | `{"type": "json_schema", "schema": {"required": ["status"]}}` |
| "Answer is grounded in docs" | `rag_faithfulness` | `{"type": "rag_faithfulness", "threshold": 0.6}` |
| "Agent used correct tool" | `tool_called` | `{"type": "tool_called", "tool": "create_refund"}` |
| "Overall task completed" | `goal_completion` | `{"type": "goal_completion", "goal": "Process refund", "threshold": 0.5}` |

## Step 3: Build Your First JSONL Dataset

Take your most common failure modes and convert them to test cases. Start with 10-20 cases.

### Converting a Spreadsheet Row

Suppose your spreadsheet has:

| Input | Expected Behavior | Pass/Fail |
|-------|------------------|-----------|
| "Can I get a refund?" | Should mention refund policy and 30-day window | Pass |

This becomes:

```json
{
  "id": "manual_001",
  "input": "Can I get a refund?",
  "expected": {
    "assertions": [
      {"type": "contains", "value": "refund"},
      {"type": "regex", "pattern": "/30 day|30-day|thirty day/i"},
      {"type": "not_contains", "value": "I don't know"}
    ]
  },
  "metadata": {
    "goal": "Explain refund policy including 30-day window"
  }
}
```

### Building the File

Create `storage/ai-evals/my-first-suite.jsonl` with one JSON object per line. Each line must have:

- `id`: Unique identifier
- `input`: The user message
- `expected.assertions`: Array of assertion objects

Tips for your first dataset:

- **Start with failures.** Your most valuable test cases are the ones that have broken before.
- **Include edge cases.** Questions that are ambiguous, adversarial, or off-topic.
- **Cover your top 5 user intents.** The most common questions your chatbot receives.

## Step 4: Install and Configure

```bash
composer require php-evals/core php-evals/laravel
php artisan ai:eval:init
```

Configure your model client in `config/ai-evals.php` (see [Tutorial 01](./01-chatbot-testing.md) for details).

## Step 5: Run Your First Automated Eval

```bash
php artisan ai:eval my-first-suite
```

Review the results. Some cases may fail -- that is expected. Adjust thresholds or assertions based on what you learn.

Store the run:

```bash
php artisan ai:eval my-first-suite --store-runs --run-id=first-baseline
```

## Step 6: Iterate and Expand

### Week 1-2: Foundation

- Start with 10-20 cases covering your most critical scenarios.
- Use `contains`, `not_contains`, and `regex` -- the simplest assertion types.
- Set lenient thresholds.

### Week 3-4: Add Depth

- Add `semantic_similarity` for cases where exact wording varies.
- Add `llm_judge_rubric` for tone and quality assessment.
- Expand to 30-50 cases.

### Month 2+: Scale

- Add `rag_faithfulness` if you have a RAG pipeline.
- Add `tool_called` and `tool_args_schema` for agent workflows.
- Add `json_schema` for structured outputs.
- Target 100+ cases for comprehensive coverage.
- Move to queue-based execution for speed (see [Tutorial 05](./05-queue-at-scale.md)).

### Adding Cases from Production

When you discover a failure in production:

1. Capture the input and the bad output.
2. Write assertions that would have caught the failure.
3. Add the case to your JSONL dataset.
4. Run the suite to verify the new case catches the issue.
5. Fix the underlying problem (prompt, model, retrieval).
6. Update the baseline.

## Step 7: Integrate into CI

Once your suite is stable (running green consistently), add it to CI:

```bash
php artisan ai:eval my-first-suite --store-runs --run-id=candidate
php artisan ai:eval:compare first-baseline candidate \
    --fail-threshold=pass_rate_drop:0.02,avg_score_drop:0.05
```

See [Tutorial 04: CI/CD Regression](./04-ci-cd-regression.md) for the full GitHub Actions setup.

## Before and After

| | Manual | Automated |
|---|---|---|
| **Time per cycle** | 2-4 hours | 5-15 minutes |
| **Consistency** | Varies by reviewer | Same assertions every time |
| **Scalability** | 10-20 cases max | 500+ cases via queues |
| **Frequency** | Before major releases | Every PR |
| **History** | Spreadsheet notes | Stored runs with full metrics |
| **CI integration** | None | Exit codes gate merges |

## Common Migration Pitfalls

1. **Starting too strict.** If every run fails, the metrics become noise. Start lenient and tighten.
2. **Too few cases.** Ten cases that all pass do not give confidence. Aim for at least 20 covering different scenarios.
3. **Forgetting to update baselines.** After an intentional improvement, update the baseline. Otherwise, the comparison will flag the improvement as "no regression" but you miss validating the new quality level.
4. **Testing only the happy path.** Include adversarial inputs, edge cases, and scenarios where the model should decline to answer.
5. **Not caching.** Enable `--cache-enabled` to avoid paying for identical inputs during iteration.

## Next Steps

- **Start here**: [Tutorial 01: Chatbot Testing](./01-chatbot-testing.md)
- **RAG evaluation**: [Tutorial 02: RAG Evaluation](./02-rag-evaluation.md)
- **All assertion types**: See `docs/assertions.md`
