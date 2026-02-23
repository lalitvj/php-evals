<?php

declare(strict_types=1);

namespace PhpEvals\Core\Contracts;

use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;

interface Assertion
{
    public function type(): string;

    /**
     * @param  array<string, mixed>  $definition
     */
    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult;
}
