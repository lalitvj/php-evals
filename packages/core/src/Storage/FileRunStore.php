<?php

declare(strict_types=1);

namespace PhpEvals\Core\Storage;

use PhpEvals\Core\Contracts\RunStore;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;

final class FileRunStore implements RunStore
{
    public function __construct(private readonly string $directory)
    {
        if (! is_dir($this->directory) && ! @mkdir($this->directory, 0777, true) && ! is_dir($this->directory)) {
            throw new RuntimeConfigurationException(sprintf('Unable to create run store directory: %s', $this->directory));
        }
    }

    public function createRun(array $payload, ?string $runId = null): string
    {
        $id = $this->sanitizeRunId($runId ?? $this->generateRunId());
        $file = $this->pathFor($id);

        $run = array_merge([
            'id' => $id,
            'created_at' => gmdate(DATE_ATOM),
            'updated_at' => gmdate(DATE_ATOM),
        ], $payload);

        $this->writeJson($file, $run);

        return $id;
    }

    public function updateRun(string $runId, array $payload): void
    {
        $id = $this->sanitizeRunId($runId);
        $existing = $this->getRun($id) ?? ['id' => $id, 'created_at' => gmdate(DATE_ATOM)];
        $merged = $this->mergeRecursive($existing, $payload);
        $merged['updated_at'] = gmdate(DATE_ATOM);

        $this->writeJson($this->pathFor($id), $merged);
    }

    public function getRun(string $runId): ?array
    {
        $file = $this->pathFor($this->sanitizeRunId($runId));
        if (! is_file($file)) {
            return null;
        }

        $contents = file_get_contents($file);
        if (! is_string($contents) || $contents === '') {
            return null;
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function listRuns(int $limit = 20): array
    {
        $files = glob(rtrim($this->directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.json') ?: [];
        rsort($files);

        $runs = [];
        foreach (array_slice($files, 0, max(1, $limit)) as $file) {
            $contents = file_get_contents($file);
            if (! is_string($contents) || $contents === '') {
                continue;
            }

            $decoded = json_decode($contents, true);
            if (is_array($decoded)) {
                $runs[] = $decoded;
            }
        }

        return $runs;
    }

    private function generateRunId(): string
    {
        return 'run_'.date('Ymd_His').'_'.bin2hex(random_bytes(4));
    }

    private function sanitizeRunId(string $runId): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $runId);

        return is_string($sanitized) && $sanitized !== '' ? $sanitized : 'run_'.bin2hex(random_bytes(4));
    }

    private function pathFor(string $runId): string
    {
        return rtrim($this->directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$runId.'.json';
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $updates
     * @return array<string, mixed>
     */
    private function mergeRecursive(array $base, array $updates): array
    {
        foreach ($updates as $key => $value) {
            if (is_array($value) && is_array($base[$key] ?? null)) {
                $base[$key] = $this->mergeRecursive($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeJson(string $path, array $payload): void
    {
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (! is_string($encoded)) {
            throw new RuntimeConfigurationException(sprintf('Unable to encode run payload for %s.', $path));
        }

        file_put_contents($path, $encoded);
    }
}
