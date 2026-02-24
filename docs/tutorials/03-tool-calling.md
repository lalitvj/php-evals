# Test Your AI Agent's Tool Calling

## The Problem

AI agents that call functions (tools) can fail in subtle ways:

- **Wrong tool**: The agent calls `search_orders` when it should call `create_refund`.
- **Missing tools**: A two-step workflow only executes the first step.
- **Bad arguments**: The agent calls the right tool but passes `"orderId"` instead of `"order_id"`.
- **Extra tools**: The agent calls tools it should not.

These failures are dangerous because the agent's text response may look correct while its actions are wrong.

## Tool Assertion Types

php-evals provides five assertion types for testing tool-calling agents.

### tool_called

Checks whether a specific tool was invoked at least once.

```json
{"type": "tool_called", "tool": "create_refund"}
```

### tool_call_count

Checks the number of tool calls. Supports `exact`, `min`, and `max`.

```json
{"type": "tool_call_count", "min": 2}
```

```json
{"type": "tool_call_count", "exact": 1}
```

### tool_args_schema

Validates the arguments of a named tool call against a JSON Schema.

```json
{
  "type": "tool_args_schema",
  "tool": "create_refund",
  "schema": {
    "type": "object",
    "required": ["order_id", "reason"],
    "properties": {
      "order_id": {"type": "string"},
      "reason": {"type": "string"}
    }
  }
}
```

### tool_call_accuracy

Checks whether the agent called the expected set of tools. With `exact: true`, no extra tools are allowed.

```json
{
  "type": "tool_call_accuracy",
  "expected": ["get_order_status", "send_email"],
  "exact": true
}
```

### goal_completion

High-level assessment that compares the model's text output against a goal description.

```json
{"type": "goal_completion", "goal": "Cancel the subscription and confirm via email", "threshold": 0.5}
```

## Step 1: Create Your Dataset

Create `storage/ai-evals/agent-tools.jsonl`:

```jsonl
{"id":"agent_001","input":"Create a refund for order ORD-7890 because the customer received a damaged item","expected":{"assertions":[{"type":"tool_called","tool":"create_refund"},{"type":"tool_call_accuracy","expected":["create_refund"],"exact":true},{"type":"tool_args_schema","tool":"create_refund","schema":{"type":"object","required":["order_id","reason"],"properties":{"order_id":{"type":"string"},"reason":{"type":"string"}}}}]},"metadata":{"goal":"Create a refund with the correct order ID and reason"}}
{"id":"agent_002","input":"Look up the shipping status of order ORD-4567 and send an update email to the customer","expected":{"assertions":[{"type":"tool_call_count","min":2},{"type":"tool_call_accuracy","expected":["get_order_status","send_email"],"exact":true}]},"metadata":{"goal":"Check order status then notify customer via email"}}
{"id":"agent_003","input":"Search for all orders from last week that are still pending","expected":{"assertions":[{"type":"tool_called","tool":"search_orders"},{"type":"tool_call_count","exact":1}]},"metadata":{"goal":"Query orders with correct date range and status filter"}}
{"id":"agent_004","input":"Cancel the subscription for customer CUST-5678 and send them a confirmation email","expected":{"assertions":[{"type":"tool_call_count","min":2},{"type":"tool_call_accuracy","expected":["cancel_subscription","send_email"],"exact":true},{"type":"goal_completion","goal":"Cancel the subscription and confirm via email","threshold":0.5}]},"metadata":{"goal":"Cancel subscription then send confirmation"}}
```

## Step 2: Adapter Setup

Your model client must return `ToolCall` objects in `ModelResponse`. Here is the key pattern from the OpenAI adapter:

```php
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Data\ToolCall;

$toolCalls = [];
foreach ($message['tool_calls'] ?? [] as $tc) {
    $fn = $tc['function'] ?? [];
    $args = json_decode($fn['arguments'] ?? '{}', true) ?: [];
    $toolCalls[] = new ToolCall($fn['name'] ?? '', $args);
}

return new ModelResponse(
    output: $textContent,
    toolCalls: $toolCalls,
    promptTokens: $promptTokens,
    completionTokens: $completionTokens,
);
```

For Anthropic, tool calls arrive as `tool_use` content blocks:

```php
foreach ($data['content'] ?? [] as $block) {
    if (($block['type'] ?? '') === 'tool_use') {
        $toolCalls[] = new ToolCall(
            $block['name'] ?? '',
            is_array($block['input'] ?? null) ? $block['input'] : [],
        );
    }
}
```

If your adapter returns an empty `toolCalls` array, every tool assertion will fail.

## Step 3: Run Evals

```bash
php artisan ai:eval agent-tools
```

### Passing Output

```
+------------+--------+-------+
| Case       | Status | Score |
+------------+--------+-------+
| agent_001  | PASS   | 1.000 |
| agent_002  | PASS   | 1.000 |
| agent_003  | PASS   | 1.000 |
| agent_004  | PASS   | 1.000 |
+------------+--------+-------+

Suite: agent-tools | Passed: 4 | Failed: 0 | Pass rate: 100.0%
```

### Failure Output

```
+------------+--------+-------+------------------------------------------+
| Case       | Status | Score | Details                                  |
+------------+--------+-------+------------------------------------------+
| agent_001  | FAIL   | 0.667 | tool_args_schema: Missing required field  |
|            |        |       | "reason"                                 |
| agent_002  | FAIL   | 0.500 | tool_call_accuracy: missing ["send_email"]|
+------------+--------+-------+------------------------------------------+
```

## Multi-Step Agents

For agents that must call multiple tools in sequence, combine assertions:

```json
{
  "assertions": [
    {"type": "tool_call_count", "min": 3, "max": 3},
    {"type": "tool_call_accuracy", "expected": ["get_order", "check_eligibility", "create_refund"], "exact": true},
    {"type": "tool_args_schema", "tool": "create_refund", "schema": {"type": "object", "required": ["order_id", "reason"]}},
    {"type": "goal_completion", "goal": "Complete the three-step return workflow", "threshold": 0.5}
  ]
}
```

Tips:

- Use `exact: true` when the agent should not call extra tools. Use `exact: false` when optional helpers are acceptable.
- Validate arguments on the most critical tools (e.g., refund, not lookup).
- Combine with `goal_completion` to catch correct tools but confusing text responses.

## Next Steps

- **CI/CD regression testing**: See [Tutorial 04: CI/CD](./04-ci-cd-regression.md)
- **Structured output validation**: See [Tutorial 06: Structured Output](./06-structured-output.md)
- **Scale with queues**: See [Tutorial 05: Queue at Scale](./05-queue-at-scale.md)
