# Structured Output Validation

## The Problem

LLMs that return JSON can fail in ways unit tests do not catch:

- Malformed JSON (missing closing braces, trailing commas)
- Missing required fields
- Wrong data types (string instead of number)
- Invalid enum values

## The Solution

The `json_schema` assertion validates model output against a JSON Schema automatically.

## Step 1: Understanding json_schema Assertions

```json
{
  "type": "json_schema",
  "schema": {
    "type": "object",
    "required": ["product", "price", "currency"],
    "properties": {
      "product": {"type": "string"},
      "price": {"type": "number"},
      "currency": {"type": "string"}
    }
  }
}
```

This asserts that the model output:
1. Is valid JSON
2. Is an object with the three required fields
3. Each field has the correct type

### Supported Schema Features

| Feature | Example |
|---------|---------|
| Required fields | `"required": ["name", "email"]` |
| Type checking | `"type": "string"`, `"number"`, `"integer"`, `"boolean"`, `"array"`, `"object"` |
| Enums | `"enum": ["low", "medium", "high"]` |
| Nested objects | `"properties": {"address": {"type": "object", "properties": {...}}}` |
| Arrays with items | `"items": {"type": "object", "required": ["id"]}` |
| Min/max items | `"minItems": 1, "maxItems": 10` |
| Number constraints | `"minimum": 0, "maximum": 1` |

## Step 2: Create Your Dataset

Create `storage/ai-evals/structured-output.jsonl`:

**Product extraction:**

```json
{
  "id": "struct_001",
  "input": "Extract the product name, price, and currency from: 'The new MacBook Pro costs $2,499 in the US store'",
  "expected": {
    "assertions": [
      {
        "type": "json_schema",
        "schema": {
          "type": "object",
          "required": ["product", "price", "currency"],
          "properties": {
            "product": {"type": "string"},
            "price": {"type": "number"},
            "currency": {"type": "string"}
          }
        }
      },
      {"type": "contains", "value": "MacBook"}
    ]
  }
}
```

**Ticket classification with enums:**

```json
{
  "id": "struct_002",
  "input": "Classify this ticket: 'My order #ORD-5678 arrived broken. I need a replacement ASAP.'",
  "expected": {
    "assertions": [
      {
        "type": "json_schema",
        "schema": {
          "type": "object",
          "required": ["category", "priority"],
          "properties": {
            "category": {"type": "string", "enum": ["billing", "shipping", "product", "account", "other"]},
            "priority": {"type": "string", "enum": ["low", "medium", "high", "urgent"]}
          }
        }
      }
    ]
  }
}
```

**Batch results with array validation:**

```json
{
  "id": "struct_003",
  "input": "Analyze sentiment of: 1) 'Love it!' 2) 'Worst ever.' 3) 'It's okay.'",
  "expected": {
    "assertions": [
      {
        "type": "json_schema",
        "schema": {
          "type": "object",
          "required": ["results"],
          "properties": {
            "results": {
              "type": "array",
              "minItems": 3,
              "items": {
                "type": "object",
                "required": ["sentiment", "confidence"],
                "properties": {
                  "sentiment": {"type": "string", "enum": ["positive", "negative", "neutral"]},
                  "confidence": {"type": "number", "minimum": 0, "maximum": 1}
                }
              }
            }
          }
        }
      }
    ]
  }
}
```

## Step 3: Combine with Content Assertions

Use `contains` alongside `json_schema` to verify both structure and specific values:

```json
{
  "assertions": [
    {"type": "json_schema", "schema": {"type": "object", "required": ["city"]}},
    {"type": "contains", "value": "Washington"}
  ]
}
```

The schema validates structure; `contains` validates a specific expected value.

## Step 4: Run and Interpret Results

```bash
php artisan ai:eval structured-output
```

When `json_schema` fails, the error message identifies the exact validation issue:

- `"Output is not valid JSON"` -- the model returned malformed JSON
- `"Required property 'price' is missing"` -- a required field was absent
- `"Property 'priority' must be one of: low, medium, high, urgent"` -- invalid enum value
- `"Property 'confidence' must be number, got string"` -- wrong type

## Tool Args Schema

When testing agents, use `tool_args_schema` to validate tool call arguments:

```json
{
  "type": "tool_args_schema",
  "tool": "create_refund",
  "schema": {
    "type": "object",
    "required": ["order_id", "amount"],
    "properties": {
      "order_id": {"type": "string"},
      "amount": {"type": "number", "minimum": 0}
    }
  }
}
```

This is the same JSON Schema validation but applied to tool arguments instead of the text output. See [Tutorial 03: Tool Calling](./03-tool-calling.md) for details.

## Next Steps

- **LLM-as-judge for quality assessment**: See [Tutorial 07: LLM-as-Judge](./07-llm-as-judge.md)
- **Tool calling validation**: See [Tutorial 03: Tool Calling](./03-tool-calling.md)
- **Full assertion reference**: See `docs/assertions.md`
