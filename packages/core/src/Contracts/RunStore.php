<?php

declare(strict_types=1);

namespace PhpEvals\Core\Contracts;

interface RunStore
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createRun(array $payload, ?string $runId = null): string;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateRun(string $runId, array $payload): void;

    /**
     * @return array<string, mixed>|null
     */
    public function getRun(string $runId): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRuns(int $limit = 20): array;
}
