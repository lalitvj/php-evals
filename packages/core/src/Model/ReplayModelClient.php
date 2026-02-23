<?php

declare(strict_types=1);

namespace PhpEvals\Core\Model;

use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;

final class ReplayModelClient implements ModelClient
{
    /**
     * @param  array<string, array<string, mixed>>  $responsesByCaseId
     */
    public function __construct(
        private readonly array $responsesByCaseId,
        private readonly ?ModelClient $fallback = null,
    ) {}

    public function complete(ModelRequest $request): ModelResponse
    {
        $payload = $this->responsesByCaseId[$request->caseId] ?? null;
        if (is_array($payload)) {
            return ModelResponse::fromArray($payload);
        }

        if ($this->fallback !== null) {
            return $this->fallback->complete($request);
        }

        return ModelResponse::fromArray(['output' => '']);
    }
}
