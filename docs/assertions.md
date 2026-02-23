# Assertions

Supported assertion definitions:

## String and structure checks
- `contains`
```json
{"type":"contains","value":"refund"}
```

- `not_contains`
```json
{"type":"not_contains","value":"guarantee"}
```

- `regex`
```json
{"type":"regex","pattern":"/refund/i"}
```

- `json_schema`
```json
{"type":"json_schema","schema":{"type":"object","required":["status"]}}
```

## Semantic checks
- `semantic_similarity`
```json
{"type":"semantic_similarity","reference":"Explain refund steps","threshold":0.75}
```

## LLM judge
- `llm_judge_rubric`
```json
{"type":"llm_judge_rubric","rubric":"Must mention policy and timeline","threshold":0.7}
```

## RAG metrics
- `rag_faithfulness`
```json
{"type":"rag_faithfulness","threshold":0.7}
```

- `rag_relevance`
```json
{"type":"rag_relevance","reference":"refund policy timeline","threshold":0.5}
```

- `rag_context_precision`
```json
{"type":"rag_context_precision","threshold":0.5}
```

RAG assertions expect context either:
- in assertion definition: `"context": ["..."]`
- or in case metadata: `"metadata":{"context":["..."]}`

## Tool and agent assertions
- `tool_called`
```json
{"type":"tool_called","name":"create_ticket"}
```

- `tool_call_count`
```json
{"type":"tool_call_count","min":1,"max":2}
```

- `tool_args_schema`
```json
{"type":"tool_args_schema","name":"create_ticket","schema":{"type":"object","required":["order_id"]}}
```

- `tool_call_accuracy`
```json
{"type":"tool_call_accuracy","expected":["create_ticket"],"exact":true}
```

- `goal_completion`
```json
{"type":"goal_completion","goal":"Create ticket and share next steps","threshold":0.6}
```
