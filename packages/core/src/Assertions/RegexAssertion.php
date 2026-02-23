<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;

final class RegexAssertion implements Assertion
{
    public function type(): string
    {
        return 'regex';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $pattern = $definition['pattern'] ?? null;

        if (! is_string($pattern) || $pattern === '') {
            return AssertionResult::fail($this->type(), 'regex.pattern must be a non-empty string.');
        }

        $match = @preg_match($pattern, $response->output);
        if ($match === false) {
            return AssertionResult::fail($this->type(), 'regex.pattern is invalid.');
        }

        if ($match !== 1) {
            return AssertionResult::fail($this->type(), sprintf('Output does not match pattern %s.', $pattern));
        }

        return AssertionResult::pass($this->type());
    }
}
