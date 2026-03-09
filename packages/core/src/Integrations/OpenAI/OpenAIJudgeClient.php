<?php

declare(strict_types=1);

namespace PhpEvals\Core\Integrations\OpenAI;

use PhpEvals\Core\Contracts\JudgeClient;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\JudgeResult;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;

final class OpenAIJudgeClient implements JudgeClient
{
    private readonly OpenAIHttpTransport $transport;

    private readonly string $model;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $this->transport = new OpenAIHttpTransport($options);
        $this->model = is_string($options['model'] ?? null) && $options['model'] !== ''
            ? $options['model']
            : 'gpt-4o-mini';
    }

    public function judge(EvalCase $case, ModelResponse $response, array $definition): JudgeResult
    {
        $rubric = is_string($definition['rubric'] ?? null) && trim($definition['rubric']) !== ''
            ? trim($definition['rubric'])
            : 'Score the assistant response for correctness, completeness, and usefulness.';

        $payload = $this->transport->post('chat/completions', [
            'model' => $this->model,
            'temperature' => 0,
            'response_format' => ['type' => 'json_object'],
            'messages' => [[
                'role' => 'user',
                'content' => $this->prompt($case, $response, $rubric),
            ]],
        ]);

        $content = $payload['choices'][0]['message']['content'] ?? null;
        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeConfigurationException('OpenAI judge response did not contain JSON content.');
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            throw new RuntimeConfigurationException('OpenAI judge response did not return valid JSON.');
        }

        $score = $decoded['score'] ?? null;
        if (! is_int($score) && ! is_float($score)) {
            throw new RuntimeConfigurationException('OpenAI judge response did not include a numeric score.');
        }

        $usage = is_array($payload['usage'] ?? null) ? $payload['usage'] : [];

        return new JudgeResult(
            score: max(0.0, min(1.0, (float) $score)),
            reason: is_string($decoded['reason'] ?? null)
                ? $decoded['reason']
                : (is_string($decoded['reasoning'] ?? null) ? $decoded['reasoning'] : null),
            promptTokens: (int) ($usage['prompt_tokens'] ?? 0),
            completionTokens: (int) ($usage['completion_tokens'] ?? 0),
            cost: 0.0,
            details: [
                'model' => $this->model,
                'raw_judgment' => $decoded,
            ],
        );
    }

    private function prompt(EvalCase $case, ModelResponse $response, string $rubric): string
    {
        $metadata = $case->metadata === [] ? '{}' : (string) json_encode($case->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
You are grading the quality of an AI response.

Return only JSON with this shape:
{"score": 0.0-1.0, "reason": "short explanation"}

User input:
{$case->input}

Assistant response:
{$response->output}

Case metadata:
{$metadata}

Rubric:
{$rubric}
PROMPT;
    }
}
