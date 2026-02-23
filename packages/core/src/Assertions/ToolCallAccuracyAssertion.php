<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;

final class ToolCallAccuracyAssertion implements Assertion
{
    public function type(): string
    {
        return 'tool_call_accuracy';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $expected = $definition['expected'] ?? null;
        $exact = (bool) ($definition['exact'] ?? true);

        if (! is_array($expected) || $expected === []) {
            return AssertionResult::fail($this->type(), 'tool_call_accuracy.expected must be a non-empty array of tool names.');
        }

        $expectedNames = array_values(array_filter(array_map(static fn (mixed $name): string => is_string($name) ? $name : '', $expected), static fn (string $name): bool => $name !== ''));
        if ($expectedNames === []) {
            return AssertionResult::fail($this->type(), 'tool_call_accuracy.expected must contain valid tool names.');
        }

        $actualNames = [];
        foreach ($response->toolCalls as $toolCall) {
            $actualNames[] = $toolCall->name;
        }

        $missing = array_values(array_diff($expectedNames, $actualNames));
        $extra = array_values(array_diff($actualNames, $expectedNames));

        $score = (count($expectedNames) - count($missing)) / count($expectedNames);
        if ($missing !== [] || ($exact && $extra !== [])) {
            return AssertionResult::fail(
                $this->type(),
                'Tool-call accuracy check failed.',
                [
                    'missing' => $missing,
                    'extra' => $extra,
                    'score' => $score,
                ],
            );
        }

        return AssertionResult::pass($this->type(), ['missing' => [], 'extra' => [], 'score' => $score]);
    }
}
