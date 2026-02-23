<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Support;

use PhpEvals\Core\Support\JsonSchemaValidator;
use PHPUnit\Framework\TestCase;

final class JsonSchemaValidatorTest extends TestCase
{
    public function test_validator_accepts_valid_object(): void
    {
        $validator = new JsonSchemaValidator;
        $schema = [
            'type' => 'object',
            'required' => ['order_id'],
            'properties' => [
                'order_id' => ['type' => 'string'],
                'amount' => ['type' => 'number'],
            ],
        ];

        $errors = $validator->validate(['order_id' => 'abc', 'amount' => 12.3], $schema);

        self::assertSame([], $errors);
    }

    public function test_validator_returns_errors_for_invalid_payload(): void
    {
        $validator = new JsonSchemaValidator;
        $schema = [
            'type' => 'object',
            'required' => ['order_id'],
            'properties' => [
                'order_id' => ['type' => 'string'],
            ],
            'additionalProperties' => false,
        ];

        $errors = $validator->validate(['unexpected' => true], $schema);

        self::assertNotSame([], $errors);
        self::assertStringContainsString('required', $errors[0]);
    }

    public function test_validator_checks_array_item_types(): void
    {
        $validator = new JsonSchemaValidator;
        $schema = [
            'type' => 'array',
            'items' => ['type' => 'integer'],
        ];

        $errors = $validator->validate([1, 'two'], $schema);

        self::assertCount(1, $errors);
        self::assertStringContainsString('expected type integer', $errors[0]);
    }

    public function test_validator_supports_null_type(): void
    {
        $validator = new JsonSchemaValidator;
        $errors = $validator->validate(null, ['type' => 'null']);

        self::assertSame([], $errors);
    }
}
