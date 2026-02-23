<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;

final class NotContainsAssertion implements Assertion
{
    public function type(): string
    {
        return 'not_contains';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $value = $definition['value'] ?? null;
        $needles = is_array($value) ? $value : [$value];

        foreach ($needles as $needle) {
            if (! is_string($needle) || $needle === '') {
                return AssertionResult::fail($this->type(), 'not_contains.value must be a non-empty string or string array.');
            }

            if (str_contains($response->output, $needle)) {
                return AssertionResult::fail(
                    $this->type(),
                    sprintf('Output must not contain "%s".', $needle),
                );
            }
        }

        return AssertionResult::pass($this->type());
    }
}
