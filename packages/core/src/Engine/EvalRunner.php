<?php

declare(strict_types=1);

namespace PhpEvals\Core\Engine;

use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\CaseResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\SuiteResult;

final class EvalRunner
{
    public function __construct(
        private readonly \PhpEvals\Core\Contracts\ModelClient $modelClient,
        private readonly AssertionRegistry $assertionRegistry,
    ) {}

    /**
     * @param  array<int, EvalCase>  $cases
     */
    public function runSuite(
        string $suite,
        array $cases,
        ?string $model = null,
        bool $stopOnFailure = false,
        bool $deterministic = false,
        ?int $seed = null,
    ): SuiteResult {
        $start = microtime(true);
        $results = [];

        foreach ($cases as $case) {
            $caseStart = microtime(true);
            $requestSeed = null;
            if ($deterministic) {
                $requestSeed = $seed ?? abs(crc32($case->id));
            }

            $response = $this->modelClient->complete(new ModelRequest($case->id, $case->input, $model, $case->metadata, $requestSeed));
            $assertionResults = [];

            foreach ($case->expected['assertions'] as $definition) {
                if (! is_array($definition) || ! is_string($definition['type'] ?? null)) {
                    $assertionResults[] = AssertionResult::fail('invalid', 'Assertion definition is invalid.');

                    continue;
                }

                $type = $definition['type'];
                $assertion = $this->assertionRegistry->get($type);
                if ($assertion === null) {
                    $assertionResults[] = AssertionResult::fail($type, sprintf('Unknown assertion type "%s".', $type));

                    continue;
                }

                $assertionResults[] = $assertion->evaluate($case, $response, $definition);
            }

            $caseResult = new CaseResult(
                $case->id,
                $assertionResults,
                $response->output,
                $response->toolCalls,
                (microtime(true) - $caseStart) * 1000,
                $response->promptTokens,
                $response->completionTokens,
                $response->cost,
            );
            $results[] = $caseResult;

            if ($stopOnFailure && ! $caseResult->passed()) {
                break;
            }
        }

        return new SuiteResult($suite, $results, (microtime(true) - $start) * 1000);
    }
}
