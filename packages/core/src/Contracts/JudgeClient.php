<?php

declare(strict_types=1);

namespace PhpEvals\Core\Contracts;

use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\JudgeResult;
use PhpEvals\Core\Data\ModelResponse;

interface JudgeClient
{
    /**
     * @param  array<string, mixed>  $definition
     */
    public function judge(EvalCase $case, ModelResponse $response, array $definition): JudgeResult;
}
