# Function Calling Evals

Tool-call assertions:

- `tool_called`
```json
{"type":"tool_called","name":"create_support_ticket"}
```

- `tool_call_count`
```json
{"type":"tool_call_count","name":"create_support_ticket","exact":1}
```

- `tool_args_schema`
```json
{"type":"tool_args_schema","name":"create_support_ticket","schema":{"type":"object","required":["order_id"]}}
```
