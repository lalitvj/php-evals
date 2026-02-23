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
use PhpEvals\Core\Scoring\TokenOverlapSimilarityScorer;
use PhpEvals\Core\Support\JsonSchemaValidator;
use PHPUnit\Framework\TestCase;

final class AssertionMetadataTest extends TestCase
{
    private EvalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->case = new EvalCase('meta', 'input', ['assertions' => []]);
    }

    public function test_type_labels_are_stable(): void
    {
        self::assertSame('contains', (new ContainsAssertion)->type());
        self::assertSame('not_contains', (new NotContainsAssertion)->type());
        self::assertSame('regex', (new RegexAssertion)->type());
        self::assertSame('json_schema', (new JsonSchemaAssertion(new JsonSchemaValidator))->type());
        self::assertSame('semantic_similarity', (new SemanticSimilarityAssertion(new TokenOverlapSimilarityScorer))->type());
        self::assertSame('tool_called', (new ToolCalledAssertion)->type());
        self::assertSame('tool_call_count', (new ToolCallCountAssertion)->type());
        self::assertSame('tool_args_schema', (new ToolArgsSchemaAssertion(new JsonSchemaValidator))->type());
    }

    public function test_invalid_assertion_payloads_return_failures(): void
    {
        self::assertFalse((new ContainsAssertion)->evaluate($this->case, new ModelResponse('x'), ['value' => ''])->passed);
        self::assertFalse((new NotContainsAssertion)->evaluate($this->case, new ModelResponse('x'), ['value' => ''])->passed);
        self::assertFalse((new RegexAssertion)->evaluate($this->case, new ModelResponse('x'), ['pattern' => ''])->passed);
        self::assertFalse((new JsonSchemaAssertion(new JsonSchemaValidator))->evaluate($this->case, new ModelResponse('{}'), ['schema' => 'bad'])->passed);
        self::assertFalse((new SemanticSimilarityAssertion(new TokenOverlapSimilarityScorer))->evaluate($this->case, new ModelResponse('x'), ['reference' => ''])->passed);
        self::assertFalse((new SemanticSimilarityAssertion(new TokenOverlapSimilarityScorer))->evaluate($this->case, new ModelResponse('x'), ['reference' => 'x', 'threshold' => 'bad'])->passed);
        self::assertFalse((new ToolCalledAssertion)->evaluate($this->case, new ModelResponse('x'), ['name' => ''])->passed);
        self::assertFalse((new ToolArgsSchemaAssertion(new JsonSchemaValidator))->evaluate($this->case, new ModelResponse('x'), ['name' => '', 'schema' => []])->passed);
        self::assertFalse((new ToolArgsSchemaAssertion(new JsonSchemaValidator))->evaluate($this->case, new ModelResponse('x'), ['name' => 'a', 'schema' => 'bad'])->passed);
    }
}
