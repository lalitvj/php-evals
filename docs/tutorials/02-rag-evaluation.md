# Evaluate Your RAG Pipeline

## The Problem

Retrieval-augmented generation (RAG) pipelines can fail in ways that are hard to detect manually:

- **Hallucination**: The model generates facts not grounded in the retrieved context.
- **Irrelevance**: The answer does not address the question.
- **Low precision**: The retrieved context contains too much noise relative to the useful information.

You need automated metrics that catch these issues before they reach production.

## What You Will Learn

- How `rag_faithfulness`, `rag_relevance`, and `rag_context_precision` assertions work
- How to provide context in your test datasets
- How to tune thresholds for your quality bar

## Prerequisites

- Laravel app with php-evals installed (`composer require php-evals/core php-evals/laravel`)
- A configured model client in `config/ai-evals.php` (see [Tutorial 01](./01-chatbot-testing.md))

## RAG Assertion Types

### rag_faithfulness

Measures whether the model's answer is grounded in the provided context. A high score means the answer only contains claims supported by the context.

```json
{"type": "rag_faithfulness", "threshold": 0.6}
```

The assertion tokenizes both the model output and the context, then computes a token-overlap score. If the score is below the threshold, the assertion fails.

### rag_relevance

Measures whether the answer addresses the original question. Compares the model output against a reference description of what a good answer should cover.

```json
{"type": "rag_relevance", "reference": "refund processing timeline and business days", "threshold": 0.5}
```

### rag_context_precision

Measures how precise the retrieved context is. A high score means most of the context was useful for answering the question (low noise).

```json
{"type": "rag_context_precision", "threshold": 0.5}
```

## Step 1: Create Your Dataset

Create `storage/ai-evals/rag-quality.jsonl`. Context is provided in the `metadata.context` array:

```jsonl
{"id":"rag_001","input":"What is the refund timeline?","expected":{"assertions":[{"type":"rag_faithfulness","threshold":0.6},{"type":"rag_relevance","reference":"refund processing timeline and business days","threshold":0.5}]},"metadata":{"context":["Refunds are processed within 3-5 business days after approval.","Returns must be initiated within 30 days of purchase.","Digital products are non-refundable after 7 days."]}}
{"id":"rag_002","input":"How do I enable two-factor authentication?","expected":{"assertions":[{"type":"rag_faithfulness","threshold":0.6},{"type":"rag_context_precision","threshold":0.5}]},"metadata":{"context":["Two-factor authentication can be enabled from Settings > Security > 2FA.","Supported methods include SMS, authenticator apps, and hardware keys.","After enabling 2FA, you will need to verify on every new device login."]}}
{"id":"rag_003","input":"What are the shipping costs for international orders?","expected":{"assertions":[{"type":"rag_faithfulness","threshold":0.5},{"type":"rag_relevance","reference":"international shipping rates and delivery fees","threshold":0.4}]},"metadata":{"context":["International shipping starts at $15 for standard delivery.","Express international shipping costs $35-50 depending on the destination.","Free shipping is available for orders over $200 to select countries.","Customs duties are the responsibility of the buyer."]}}
{"id":"rag_004","input":"Can I use the API without authentication?","expected":{"assertions":[{"type":"rag_faithfulness","threshold":0.7},{"type":"rag_context_precision","threshold":0.5}]},"metadata":{"context":["All API endpoints require authentication via Bearer token.","Rate limits are 100 requests per minute for authenticated users.","Public endpoints are read-only and limited to 10 requests per minute.","API keys can be generated from the Developer Dashboard."]}}
```

### Where Context Comes From

RAG assertions expect context in one of two places:

1. **Case metadata** (recommended): `"metadata": {"context": ["chunk 1", "chunk 2"]}`
2. **Assertion definition**: `{"type": "rag_faithfulness", "context": ["chunk 1"], "threshold": 0.6}`

Using metadata keeps assertions clean and lets multiple assertions share the same context.

### Anatomy of a RAG Test Case

```json
{
  "id": "rag_001",
  "input": "What is the refund timeline?",
  "expected": {
    "assertions": [
      {"type": "rag_faithfulness", "threshold": 0.6},
      {"type": "rag_relevance", "reference": "refund processing timeline", "threshold": 0.5}
    ]
  },
  "metadata": {
    "context": [
      "Refunds are processed within 3-5 business days after approval.",
      "Returns must be initiated within 30 days of purchase."
    ]
  }
}
```

- **input**: The user's question, sent to your model.
- **context**: The documents your RAG pipeline retrieved. Provide the same chunks your pipeline would use.
- **rag_faithfulness**: Is the answer grounded in the context?
- **rag_relevance**: Does the answer address the question? The `reference` describes what a good answer covers.

## Step 2: Run Evals

```bash
php artisan ai:eval rag-quality
```

### Expected Output

```
+----------+----------------------------------------------------+--------+-------+
| Case     | Input                                              | Status | Score |
+----------+----------------------------------------------------+--------+-------+
| rag_001  | What is the refund timeline?                       | PASS   | 1.00  |
| rag_002  | How do I enable two-factor authentication?         | PASS   | 0.85  |
| rag_003  | What are the shipping costs for international...   | PASS   | 0.78  |
| rag_004  | Can I use the API without authentication?          | FAIL   | 0.55  |
+----------+----------------------------------------------------+--------+-------+

Suite: rag-quality | Cases: 4 | Passed: 3 | Failed: 1 | Pass rate: 75.0%
```

## Step 3: Tuning Thresholds

Thresholds depend on your quality bar and domain:

| Assertion | Conservative | Moderate | Lenient |
|-----------|-------------|----------|---------|
| `rag_faithfulness` | 0.8 | 0.6 | 0.4 |
| `rag_relevance` | 0.7 | 0.5 | 0.3 |
| `rag_context_precision` | 0.6 | 0.4 | 0.3 |

**Start lenient, then tighten.** Begin with moderate thresholds. Once your pipeline is stable, increase thresholds to catch subtle regressions. If you start too strict, every run will fail and the metrics become noise.

### When to Adjust

- **After improving retrieval**: Tighten `rag_context_precision` -- your pipeline should now retrieve more relevant chunks.
- **After prompt tuning**: Tighten `rag_faithfulness` -- the model should stay closer to the context.
- **After adding new content**: Lower thresholds temporarily while the pipeline adjusts, then tighten back.

## Combining RAG Assertions with Other Types

You can mix RAG assertions with string checks for comprehensive coverage:

```json
{
  "assertions": [
    {"type": "rag_faithfulness", "threshold": 0.6},
    {"type": "contains", "value": "3-5 business days"},
    {"type": "not_contains", "value": "I'm not sure"}
  ]
}
```

This verifies the answer is grounded in context AND includes the specific timeline AND does not hedge unnecessarily.

## Next Steps

- **Test tool calling**: See [Tutorial 03: Tool Calling](./03-tool-calling.md)
- **Structured output validation**: See [Tutorial 06: Structured Output](./06-structured-output.md)
- **LLM-as-judge for subjective quality**: See [Tutorial 07: LLM-as-Judge](./07-llm-as-judge.md)
- **Full assertion reference**: See `docs/assertions.md`
