<?php

declare(strict_types=1);

namespace PhpEvals\Core\Contracts;

use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;

interface ModelClient
{
    public function complete(ModelRequest $request): ModelResponse;
}
