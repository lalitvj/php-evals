# Extending php-evals

## Custom Model Client
Implement `PhpEvals\Core\Contracts\ModelClient`.

```php
final class MyModelClient implements \PhpEvals\Core\Contracts\ModelClient
{
    public function complete(\PhpEvals\Core\Data\ModelRequest $request): \PhpEvals\Core\Data\ModelResponse
    {
        return \PhpEvals\Core\Data\ModelResponse::fromArray([
            'output' => 'response text',
            'tool_calls' => [],
        ]);
    }
}
```

Register it in `php-evals.php`:

```php
return [
    'model_client' => MyModelClient::class,
    'model_client_options' => [],
];
```

Before writing your own adapter, check the built-in integrations:
- `PhpEvals\Laravel\Integrations\Prism\PrismModelClient`
- `PhpEvals\Laravel\Integrations\LaravelAI\LaravelAIModelClient`
- `PhpEvals\Core\Integrations\OpenAI\OpenAIJudgeClient`
- `PhpEvals\Core\Integrations\OpenAI\OpenAIEmbeddingSimilarityScorer`

## Custom Similarity Scorer
Implement `PhpEvals\Core\Contracts\SimilarityScorer` and set:

```php
return [
    'similarity_scorer' => MySimilarityScorer::class,
    'similarity_scorer_options' => [],
];
```

Aliases are also supported:
- `'similarity_scorer' => 'local'`
- `'similarity_scorer' => 'openai_embeddings'`
- `'judge_client' => 'local'`
- `'judge_client' => 'openai'`

## Custom Assertion Type
Implement `PhpEvals\Core\Contracts\Assertion` and register it by extending runtime setup in your own bootstrap path around `AssertionRegistry`.
