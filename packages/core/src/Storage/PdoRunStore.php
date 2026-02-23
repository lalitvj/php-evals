<?php

declare(strict_types=1);

namespace PhpEvals\Core\Storage;

use PDO;
use PhpEvals\Core\Contracts\RunStore;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;

final class PdoRunStore implements RunStore
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->ensureSchema();
    }

    public function createRun(array $payload, ?string $runId = null): string
    {
        $id = $runId ?? 'run_'.date('Ymd_His').'_'.bin2hex(random_bytes(4));
        $json = json_encode(array_merge(['id' => $id], $payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (! is_string($json)) {
            throw new RuntimeConfigurationException('Unable to encode run payload for database storage.');
        }

        $stmt = $this->pdo->prepare('INSERT INTO ai_eval_runs (id, payload, created_at, updated_at) VALUES (:id, :payload, :created_at, :updated_at)');
        $now = gmdate('Y-m-d H:i:s');
        $stmt->execute([
            ':id' => $id,
            ':payload' => $json,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return $id;
    }

    public function updateRun(string $runId, array $payload): void
    {
        $existing = $this->getRun($runId) ?? ['id' => $runId];
        $merged = $this->mergeRecursive($existing, $payload);
        $json = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (! is_string($json)) {
            throw new RuntimeConfigurationException('Unable to encode run payload for database update.');
        }

        $stmt = $this->pdo->prepare('UPDATE ai_eval_runs SET payload = :payload, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':id' => $runId,
            ':payload' => $json,
            ':updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function getRun(string $runId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT payload FROM ai_eval_runs WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $runId]);
        $payload = $stmt->fetchColumn();

        if (! is_string($payload) || $payload === '') {
            return null;
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function listRuns(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare('SELECT payload FROM ai_eval_runs ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        $runs = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $payload) {
            if (! is_string($payload) || $payload === '') {
                continue;
            }

            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $runs[] = $decoded;
            }
        }

        return $runs;
    }

    private function ensureSchema(): void
    {
        try {
            $this->pdo->exec('CREATE TABLE IF NOT EXISTS ai_eval_runs (
                id VARCHAR(191) PRIMARY KEY,
                payload LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )');
        } catch (\PDOException $e) {
            throw new RuntimeConfigurationException(
                sprintf('Unable to create ai_eval_runs table: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e,
            );
        }
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
}
