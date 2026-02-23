<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Assertions;

use PhpEvals\Core\Assertions\GoalCompletionAssertion;
use PhpEvals\Core\Assertions\LlmJudgeRubricAssertion;
use PhpEvals\Core\Assertions\RagContextPrecisionAssertion;
use PhpEvals\Core\Assertions\RagFaithfulnessAssertion;
use PhpEvals\Core\Assertions\RagRelevanceAssertion;
use PhpEvals\Core\Assertions\ToolCallAccuracyAssertion;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Data\ToolCall;
use PhpEvals\Core\Model\HeuristicJudgeClient;
use PhpEvals\Core\Scoring\TokenOverlapSimilarityScorer;
use PHPUnit\Framework\TestCase;

final class AdvancedEvaluatorAssertionsTest extends TestCase
{
    public function test_llm_judge_rubric_assertion(): void
    {
        $assertion = new LlmJudgeRubricAssertion(new HeuristicJudgeClient);
        $case = new EvalCase('a', 'input', ['assertions' => []]);
        $response = ModelResponse::fromArray(['output' => 'Provide refund policy and timeline for resolution']);

        self::assertTrue($assertion->evaluate($case, $response, ['rubric' => 'refund policy timeline', 'threshold' => 0.6])->passed);
    }

    public function test_rag_and_agent_metric_assertions(): void
    {
        $case = new EvalCase('b', 'refund question', ['assertions' => []], [
            'context' => ['Refund policy allows returns in 30 days', 'Timeline is 3-5 business days'],
            'goal' => 'Explain refund timeline and policy',
        ]);

        $response = ModelResponse::fromArray([
            'output' => 'Refund policy supports 30 day returns and timeline is 3-5 days.',
            'tool_calls' => [['name' => 'create_ticket', 'arguments' => ['order_id' => 'A1']]],
        ]);

        self::assertTrue((new RagFaithfulnessAssertion)->evaluate($case, $response, ['threshold' => 0.3])->passed);
        self::assertTrue((new RagRelevanceAssertion(new TokenOverlapSimilarityScorer))->evaluate($case, $response, ['reference' => 'refund timeline policy', 'threshold' => 0.2])->passed);
        self::assertTrue((new RagContextPrecisionAssertion)->evaluate($case, $response, ['threshold' => 0.2])->passed);
        self::assertTrue((new GoalCompletionAssertion(new TokenOverlapSimilarityScorer))->evaluate($case, $response, ['threshold' => 0.3])->passed);
        self::assertTrue((new ToolCallAccuracyAssertion)->evaluate($case, new ModelResponse('ok', [new ToolCall('create_ticket')]), ['expected' => ['create_ticket']])->passed);
    }
}
