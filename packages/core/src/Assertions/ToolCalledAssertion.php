<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;

final class ToolCalledAssertion implements Assertion
{
    public function type(): string
    {
        return 'tool_called';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $name = $definition['name'] ?? null;
        if (! is_string($name) || $name === '') {
            return AssertionResult::fail($this->type(), 'tool_called.name must be a non-empty string.');
        }

        foreach ($response->toolCalls as $toolCall) {
            if ($toolCall->name === $name) {
                return AssertionResult::pass($this->type());
            }
        }

        return AssertionResult::fail($this->type(), sprintf('Tool "%s" was not called.', $name));
    }
}
