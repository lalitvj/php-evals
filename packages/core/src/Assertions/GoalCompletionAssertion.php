<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Contracts\SimilarityScorer;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;

final class GoalCompletionAssertion implements Assertion
{
    public function __construct(private readonly SimilarityScorer $scorer) {}

    public function type(): string
    {
        return 'goal_completion';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $goal = $definition['goal'] ?? ($case->metadata['goal'] ?? null);
        $threshold = $definition['threshold'] ?? 0.7;

        if (! is_string($goal) || $goal === '') {
            return AssertionResult::fail($this->type(), 'goal_completion.goal must be a non-empty string.');
        }

        if (! is_int($threshold) && ! is_float($threshold)) {
            return AssertionResult::fail($this->type(), 'goal_completion.threshold must be numeric.');
        }

        $score = $this->scorer->score($response->output, $goal);
        if ($score < (float) $threshold) {
            return AssertionResult::fail(
                $this->type(),
                sprintf('Goal completion score %.3f is lower than threshold %.3f.', $score, (float) $threshold),
                ['score' => $score],
            );
        }

        return AssertionResult::pass($this->type(), ['score' => $score]);
    }
}
