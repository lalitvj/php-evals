<?php

declare(strict_types=1);

namespace PhpEvals\Core\Model;

use PhpEvals\Core\Contracts\ModelClient;
use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;

final class CachedModelClient implements ModelClient
{
    public function __construct(
        private readonly ModelClient $inner,
        private readonly string $cachePath,
        private readonly ?int $ttlSeconds = null,
    ) {
        if (! is_dir($this->cachePath)) {
            $created = mkdir($this->cachePath, 0777, true);
            if (! $created && ! is_dir($this->cachePath)) {
                throw new RuntimeConfigurationException(
                    sprintf('Unable to create cache directory: %s — %s', $this->cachePath, error_get_last()['message'] ?? 'unknown error'),
                );
            }
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
            if ($this->ttlSeconds !== null && (time() - (int) filemtime($file)) > $this->ttlSeconds) {
                @unlink($file);
            }

            if (is_file($file)) {
                $contents = file_get_contents($file);
                if (is_string($contents) && $contents !== '') {
                    $decoded = json_decode($contents, true);
                    if (is_array($decoded)) {
                        return ModelResponse::fromArray($decoded);
                    }
                }
            }
        }

        $response = $this->inner->complete($request);

        try {
            $encoded = json_encode($response->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (is_string($encoded)) {
                $result = file_put_contents($file, $encoded);
                if ($result === false) {
                    trigger_error(sprintf('CachedModelClient: cache write failed for %s', $file), E_USER_WARNING);
                }
            }
        } catch (\Throwable) {
            // Cache write is non-fatal; response is still returned.
        }

        return $response;
    }

    /**
     * Remove all cached response files.
     */
    public function clearCache(): int
    {
        $files = glob(rtrim($this->cachePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.json') ?: [];
        $count = 0;
        foreach ($files as $file) {
            if (is_file($file) && @unlink($file)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Remove only expired cached response files.
     */
    public function clearExpiredCache(): int
    {
        if ($this->ttlSeconds === null) {
            return 0;
        }

        $files = glob(rtrim($this->cachePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.json') ?: [];
        $count = 0;
        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && ($now - (int) filemtime($file)) > $this->ttlSeconds && @unlink($file)) {
                $count++;
            }
        }

        return $count;
    }
}
