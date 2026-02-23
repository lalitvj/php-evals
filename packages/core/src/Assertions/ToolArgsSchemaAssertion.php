<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Support\JsonSchemaValidator;

final class ToolArgsSchemaAssertion implements Assertion
{
    public function __construct(private readonly JsonSchemaValidator $validator) {}

    public function type(): string
    {
        return 'tool_args_schema';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $name = $definition['name'] ?? null;
        $schema = $definition['schema'] ?? null;

        if (! is_string($name) || $name === '') {
            return AssertionResult::fail($this->type(), 'tool_args_schema.name must be a non-empty string.');
        }

        if (! is_array($schema)) {
            return AssertionResult::fail($this->type(), 'tool_args_schema.schema must be an object.');
        }

        foreach ($response->toolCalls as $toolCall) {
            if ($toolCall->name !== $name) {
                continue;
            }

            $errors = $this->validator->validate($toolCall->arguments, $schema);
            if ($errors !== []) {
                return AssertionResult::fail($this->type(), $errors[0], ['errors' => $errors]);
            }

            return AssertionResult::pass($this->type());
        }

        return AssertionResult::fail($this->type(), sprintf('Tool "%s" was not called.', $name));
    }
}
