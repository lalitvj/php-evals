<?php

declare(strict_types=1);

namespace PhpEvals\Core\Model;

use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Exceptions\EvalExecutionException;

final class NullModelClient implements ModelClient
{
    public function complete(ModelRequest $request): ModelResponse
    {
        throw new EvalExecutionException(
            sprintf('No model client configured. Cannot evaluate case "%s".', $request->caseId),
        );
    }
}
