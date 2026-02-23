<?php

declare(strict_types=1);

namespace PhpEvals\Core\Model;

use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;

final class ArrayMapModelClient implements ModelClient
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(private readonly array $options = []) {}

    public function complete(ModelRequest $request): ModelResponse
    {
        $responses = is_array($this->options['responses'] ?? null) ? $this->options['responses'] : [];
        $payload = $responses[$request->caseId] ?? null;

        if (! is_array($payload)) {
            return new ModelResponse($request->input);
        }

        return ModelResponse::fromArray($payload);
    }
}
