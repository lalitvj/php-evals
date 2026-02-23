<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;

final class ToolCallCountAssertion implements Assertion
{
    public function type(): string
    {
        return 'tool_call_count';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $name = is_string($definition['name'] ?? null) ? $definition['name'] : null;
        $exact = $definition['exact'] ?? null;
        $min = $definition['min'] ?? null;
        $max = $definition['max'] ?? null;

        $count = 0;
        foreach ($response->toolCalls as $toolCall) {
            if ($name === null || $toolCall->name === $name) {
                $count++;
            }
        }

        if ((is_int($exact) || is_float($exact)) && $count !== (int) $exact) {
            return AssertionResult::fail($this->type(), sprintf('Expected exactly %d calls, got %d.', (int) $exact, $count));
        }

        if ((is_int($min) || is_float($min)) && $count < (int) $min) {
            return AssertionResult::fail($this->type(), sprintf('Expected at least %d calls, got %d.', (int) $min, $count));
        }

        if ((is_int($max) || is_float($max)) && $count > (int) $max) {
            return AssertionResult::fail($this->type(), sprintf('Expected at most %d calls, got %d.', (int) $max, $count));
        }

        return AssertionResult::pass($this->type());
    }
}
