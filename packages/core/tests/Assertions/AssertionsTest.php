<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Assertions;

use PhpEvals\Core\Assertions\ContainsAssertion;
use PhpEvals\Core\Assertions\JsonSchemaAssertion;
use PhpEvals\Core\Assertions\NotContainsAssertion;
use PhpEvals\Core\Assertions\RegexAssertion;
use PhpEvals\Core\Assertions\SemanticSimilarityAssertion;
use PhpEvals\Core\Assertions\ToolArgsSchemaAssertion;
use PhpEvals\Core\Assertions\ToolCallCountAssertion;
use PhpEvals\Core\Assertions\ToolCalledAssertion;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Data\ToolCall;
use PhpEvals\Core\Scoring\TokenOverlapSimilarityScorer;
use PhpEvals\Core\Support\JsonSchemaValidator;
use PHPUnit\Framework\TestCase;

final class AssertionsTest extends TestCase
{
    private EvalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->case = new EvalCase('case-1', 'input', ['assertions' => []]);
    }

    public function test_contains_assertion(): void
    {
        $assertion = new ContainsAssertion;
        $pass = $assertion->evaluate($this->case, new ModelResponse('hello refund'), ['value' => 'refund']);
        $fail = $assertion->evaluate($this->case, new ModelResponse('hello world'), ['value' => 'refund']);

        self::assertTrue($pass->passed);
        self::assertFalse($fail->passed);
    }

    public function test_not_contains_assertion(): void
    {
        $assertion = new NotContainsAssertion;
        $pass = $assertion->evaluate($this->case, new ModelResponse('hello world'), ['value' => 'guarantee']);
        $fail = $assertion->evaluate($this->case, new ModelResponse('includes guarantee'), ['value' => 'guarantee']);

        self::assertTrue($pass->passed);
        self::assertFalse($fail->passed);
    }

    public function test_regex_assertion(): void
    {
        $assertion = new RegexAssertion;
        $pass = $assertion->evaluate($this->case, new ModelResponse('order #123'), ['pattern' => '/#\d+/']);
        $invalid = $assertion->evaluate($this->case, new ModelResponse('order #123'), ['pattern' => '/[']);
        $fail = $assertion->evaluate($this->case, new ModelResponse('order abc'), ['pattern' => '/#\d+/']);

        self::assertTrue($pass->passed);
        self::assertFalse($invalid->passed);
        self::assertFalse($fail->passed);
    }

    public function test_json_schema_assertion(): void
    {
        $assertion = new JsonSchemaAssertion(new JsonSchemaValidator);
        $schema = [
            'type' => 'object',
            'required' => ['refund'],
            'properties' => [
                'refund' => ['type' => 'boolean'],
            ],
        ];

        $pass = $assertion->evaluate($this->case, new ModelResponse('{"refund":true}'), ['schema' => $schema]);
        $fail = $assertion->evaluate($this->case, new ModelResponse('{"refund":"yes"}'), ['schema' => $schema]);
        $invalid = $assertion->evaluate($this->case, new ModelResponse('not-json'), ['schema' => $schema]);

        self::assertTrue($pass->passed);
        self::assertFalse($fail->passed);
        self::assertFalse($invalid->passed);
    }

    public function test_semantic_similarity_assertion(): void
    {
        $assertion = new SemanticSimilarityAssertion(new TokenOverlapSimilarityScorer);
        $pass = $assertion->evaluate(
            $this->case,
            new ModelResponse('refund process and next steps'),
            ['reference' => 'refund process steps', 'threshold' => 0.3],
        );
        $fail = $assertion->evaluate(
            $this->case,
            new ModelResponse('shipments only'),
            ['reference' => 'refund process steps', 'threshold' => 0.8],
        );

        self::assertTrue($pass->passed);
        self::assertFalse($fail->passed);
    }

    public function test_tool_called_assertion(): void
    {
        $assertion = new ToolCalledAssertion;
        $response = new ModelResponse('ok', [new ToolCall('create_ticket', ['order_id' => 'A1'])]);

        self::assertTrue($assertion->evaluate($this->case, $response, ['name' => 'create_ticket'])->passed);
        self::assertFalse($assertion->evaluate($this->case, $response, ['name' => 'missing'])->passed);
    }

    public function test_tool_call_count_assertion(): void
    {
        $assertion = new ToolCallCountAssertion;
        $response = new ModelResponse('ok', [new ToolCall('a'), new ToolCall('a'), new ToolCall('b')]);

        self::assertTrue($assertion->evaluate($this->case, $response, ['name' => 'a', 'exact' => 2])->passed);
        self::assertTrue($assertion->evaluate($this->case, $response, ['min' => 2, 'max' => 4])->passed);
        self::assertFalse($assertion->evaluate($this->case, $response, ['name' => 'a', 'min' => 3])->passed);
        self::assertFalse($assertion->evaluate($this->case, $response, ['name' => 'a', 'exact' => 1])->passed);
        self::assertFalse($assertion->evaluate($this->case, $response, ['name' => 'a', 'max' => 1])->passed);
    }

    public function test_tool_args_schema_assertion(): void
    {
        $assertion = new ToolArgsSchemaAssertion(new JsonSchemaValidator);
        $schema = [
            'type' => 'object',
            'required' => ['order_id'],
            'properties' => [
                'order_id' => ['type' => 'string'],
            ],
        ];
        $response = new ModelResponse('ok', [new ToolCall('create_ticket', ['order_id' => 'A1'])]);
        $badResponse = new ModelResponse('ok', [new ToolCall('create_ticket', ['order_id' => 12])]);

        self::assertTrue($assertion->evaluate($this->case, $response, ['name' => 'create_ticket', 'schema' => $schema])->passed);
        self::assertFalse($assertion->evaluate($this->case, $badResponse, ['name' => 'create_ticket', 'schema' => $schema])->passed);
        self::assertFalse($assertion->evaluate($this->case, $response, ['name' => 'missing', 'schema' => $schema])->passed);
    }
}
