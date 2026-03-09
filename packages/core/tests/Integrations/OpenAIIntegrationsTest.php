<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Integrations;

use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Engine\RuntimeFactory;
use PhpEvals\Core\Integrations\OpenAI\OpenAIEmbeddingSimilarityScorer;
use PhpEvals\Core\Integrations\OpenAI\OpenAIJudgeClient;
use PHPUnit\Framework\TestCase;

final class OpenAIIntegrationsTest extends TestCase
{
    public function test_embedding_similarity_scorer_uses_openai_payload_shape(): void
    {
        $scorer = new OpenAIEmbeddingSimilarityScorer([
            'api_key' => 'test-key',
            'request_handler' => static function (): array {
                return [
                    'data' => [
                        ['embedding' => [1.0, 0.0, 0.0]],
                        ['embedding' => [1.0, 0.0, 0.0]],
                    ],
                ];
            },
        ]);

        self::assertSame(1.0, $scorer->score('refund approved', 'refund approved'));
    }

    public function test_openai_judge_client_parses_json_judgment(): void
    {
        $judge = new OpenAIJudgeClient([
            'api_key' => 'test-key',
            'request_handler' => static function (): array {
                return [
                    'choices' => [[
                        'message' => [
                            'content' => '{"score":0.85,"reason":"Strong answer."}',
                        ],
                    ]],
                    'usage' => [
                        'prompt_tokens' => 10,
                        'completion_tokens' => 5,
                    ],
                ];
            },
        ]);

        $result = $judge->judge(
            new EvalCase('case-1', 'How do refunds work?', ['assertions' => []]),
            ModelResponse::fromArray(['output' => 'Refunds are processed in 3-5 business days.']),
            ['rubric' => 'Must explain refund timing clearly.'],
        );

        self::assertSame(0.85, $result->score);
        self::assertSame('Strong answer.', $result->reason);
        self::assertSame(10, $result->promptTokens);
        self::assertSame(5, $result->completionTokens);
    }

    public function test_runtime_factory_supports_openai_aliases(): void
    {
        $factory = new RuntimeFactory;
        $config = \PhpEvals\Core\Config\CoreConfig::fromArray([
            'similarity_scorer' => 'openai_embeddings',
            'similarity_scorer_options' => [
                'api_key' => 'test-key',
                'request_handler' => static function (): array {
                    return [
                        'data' => [
                            ['embedding' => [1.0, 0.0]],
                            ['embedding' => [0.0, 1.0]],
                        ],
                    ];
                },
            ],
            'judge_client' => 'openai',
            'judge_client_options' => [
                'api_key' => 'test-key',
                'request_handler' => static function (): array {
                    return [
                        'choices' => [[
                            'message' => [
                                'content' => '{"score":0.6,"reason":"Adequate."}',
                            ],
                        ]],
                    ];
                },
            ],
        ]);

        self::assertSame(0.0, $factory->buildSimilarityScorer($config)->score('left', 'right'));
        self::assertSame(
            0.6,
            $factory->buildJudgeClient($config)->judge(
                new EvalCase('case-1', 'input', ['assertions' => []]),
                new ModelResponse('output'),
                [],
            )->score,
        );
    }
}
