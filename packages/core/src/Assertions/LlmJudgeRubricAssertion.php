<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Contracts\JudgeClient;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;

final class LlmJudgeRubricAssertion implements Assertion
{
    public function __construct(private readonly JudgeClient $judgeClient) {}

    public function type(): string
    {
        return 'llm_judge_rubric';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $threshold = $definition['threshold'] ?? 0.7;
        if (! is_int($threshold) && ! is_float($threshold)) {
            return AssertionResult::fail($this->type(), 'llm_judge_rubric.threshold must be numeric.');
        }

        $result = $this->judgeClient->judge($case, $response, $definition);

        if ($result->score < (float) $threshold) {
            return AssertionResult::fail(
                $this->type(),
                sprintf('Judge score %.3f is lower than threshold %.3f.', $result->score, (float) $threshold),
                $result->toArray(),
            );
        }

        return AssertionResult::pass($this->type(), $result->toArray());
    }
}
