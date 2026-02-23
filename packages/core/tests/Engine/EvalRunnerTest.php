<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Engine;

use PhpEvals\Core\Assertions\ContainsAssertion;
use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Engine\AssertionRegistry;
use PhpEvals\Core\Engine\EvalRunner;
use PHPUnit\Framework\TestCase;

final class EvalRunnerTest extends TestCase
{
    public function test_runner_marks_failures_and_stop_on_failure(): void
    {
        $cases = [
            new EvalCase('case-1', 'input', ['assertions' => [['type' => 'contains', 'value' => 'ok']]]),
            new EvalCase('case-2', 'input', ['assertions' => [['type' => 'contains', 'value' => 'ok']]]),
        ];

        $runner = new EvalRunner(new EvalRunnerModelClient(['case-1' => 'bad', 'case-2' => 'ok']), new AssertionRegistry([new ContainsAssertion]));
        $result = $runner->runSuite('suite-a', $cases, null, true);

        self::assertCount(1, $result->caseResults);
        self::assertFalse($result->allPassed());
    }

    public function test_runner_handles_unknown_assertion_type(): void
    {
        $cases = [new EvalCase('case-1', 'input', ['assertions' => [['type' => 'unknown']]])];
        $runner = new EvalRunner(new EvalRunnerModelClient(['case-1' => 'ok']), new AssertionRegistry([]));

        $result = $runner->runSuite('suite-a', $cases);

        self::assertSame(1, $result->failedCases());
        self::assertStringContainsString('Unknown assertion type', (string) $result->caseResults[0]->assertionResults[0]->message);
    }

    public function test_runner_handles_invalid_assertion_payload(): void
    {
        $cases = [new EvalCase('case-1', 'input', ['assertions' => ['bad-definition']])];
        $runner = new EvalRunner(new EvalRunnerModelClient(['case-1' => 'ok']), new AssertionRegistry([]));

        $result = $runner->runSuite('suite-a', $cases);

        self::assertSame('invalid', $result->caseResults[0]->assertionResults[0]->type);
    }
}

final class EvalRunnerModelClient implements ModelClient
{
    /**
     * @param  array<string, string>  $responses
     */
    public function __construct(private readonly array $responses) {}

    public function complete(ModelRequest $request): ModelResponse
    {
        return new ModelResponse($this->responses[$request->caseId] ?? '');
    }
}
