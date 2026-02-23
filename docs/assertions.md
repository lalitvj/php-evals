# Assertions

Supported assertion definitions:

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

- `semantic_similarity`
```json
{"type":"semantic_similarity","reference":"Explain refund steps","threshold":0.75}
```
