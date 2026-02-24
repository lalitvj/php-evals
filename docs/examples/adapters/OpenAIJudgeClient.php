<?php

declare(strict_types=1);

namespace App\AI;

use GuzzleHttp\Client;
use PhpEvals\Core\Contracts\JudgeClient;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\JudgeResult;
use PhpEvals\Core\Data\ModelResponse;

/**
 * OpenAI-powered judge for llm_judge_rubric assertions.
 *
 * Sends the eval case, model response, and rubric to GPT-4o and asks it
 * to score the response on a 0-10 scale with reasoning.
 *
 * Usage in config/ai-evals.php:
 *   'judge_client' => \App\AI\OpenAIJudgeClient::class,
 *   'judge_client_options' => [
 *       'api_key' => env('OPENAI_API_KEY'),
 *       'model'   => 'gpt-4o',
 *   ],
 */
final class OpenAIJudgeClient implements JudgeClient
{
    private Client $http;

    private string $model;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $apiKey = $options['api_key'] ?? env('OPENAI_API_KEY', '');
        $this->model = $options['model'] ?? 'gpt-4o';

        $this->http = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => (int) ($options['timeout'] ?? 120),
        ]);
    }

    public function judge(EvalCase $case, ModelResponse $response, array $definition): JudgeResult
    {
        $rubric = $definition['rubric'] ?? 'Rate the quality of the response.';

        $prompt = <<<PROMPT
            You are an evaluation judge. Score the following AI response on a scale of 0.0 to 1.0.

            ## User Input
            {$case->input}

            ## AI Response
            {$response->output}

            ## Rubric
            {$rubric}

            Respond with ONLY a JSON object: {"score": <float 0.0-1.0>, "reasoning": "<brief explanation>"}
            PROMPT;

        $apiResponse = $this->http->post('chat/completions', [
            'json' => [
                'model' => $this->model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'response_format' => ['type' => 'json_object'],
            ],
        ]);

        $data = json_decode($apiResponse->getBody()->getContents(), true);
        $content = $data['choices'][0]['message']['content'] ?? '{}';
        $parsed = json_decode($content, true) ?: [];

        $score = is_numeric($parsed['score'] ?? null) ? (float) $parsed['score'] : 0.0;
        $reasoning = is_string($parsed['reasoning'] ?? null) ? $parsed['reasoning'] : '';

        return new JudgeResult(
            score: max(0.0, min(1.0, $score)),
            passed: $score >= ($definition['threshold'] ?? 0.7),
            reasoning: $reasoning,
        );
    }
}
