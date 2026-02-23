<?php

declare(strict_types=1);

namespace PhpEvals\Core\Model;

use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;

final class CachedModelClient implements ModelClient
{
    public function __construct(
        private readonly ModelClient $inner,
        private readonly string $cachePath,
    ) {
        if (! is_dir($this->cachePath)) {
            @mkdir($this->cachePath, 0777, true);
        }
    }

    public function complete(ModelRequest $request): ModelResponse
    {
        $key = sha1(json_encode([
            'case_id' => $request->caseId,
            'input' => $request->input,
            'model' => $request->model,
            'metadata' => $request->metadata,
            'seed' => $request->seed,
        ], JSON_UNESCAPED_SLASHES) ?: '');

        $file = rtrim($this->cachePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$key.'.json';
        if (is_file($file)) {
            $contents = file_get_contents($file);
            if (is_string($contents) && $contents !== '') {
                $decoded = json_decode($contents, true);
                if (is_array($decoded)) {
                    return ModelResponse::fromArray($decoded);
                }
            }
        }

        $response = $this->inner->complete($request);
        $encoded = json_encode($response->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (is_string($encoded)) {
            file_put_contents($file, $encoded);
        }

        return $response;
    }
}
