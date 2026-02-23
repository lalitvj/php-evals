<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Support\JsonSchemaValidator;

final class JsonSchemaAssertion implements Assertion
{
    public function __construct(private readonly JsonSchemaValidator $validator) {}

    public function type(): string
    {
        return 'json_schema';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $schema = $definition['schema'] ?? null;
        if (! is_array($schema)) {
            return AssertionResult::fail($this->type(), 'json_schema.schema must be an object.');
        }

        $decoded = json_decode($response->output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return AssertionResult::fail($this->type(), 'Output is not valid JSON.');
        }

        $errors = $this->validator->validate($decoded, $schema);
        if ($errors !== []) {
            return AssertionResult::fail($this->type(), $errors[0], ['errors' => $errors]);
        }

        return AssertionResult::pass($this->type());
    }
}
