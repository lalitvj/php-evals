<?php

declare(strict_types=1);

namespace PhpEvals\Core\Engine;

use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Exceptions\InvalidEvalCaseException;

final class SuiteLoader
{
    public function __construct(
        private readonly string $datasetPath,
        private readonly CaseValidator $validator,
    ) {}

    /**
     * @return array<int, string>
     */
    public function listSuites(): array
    {
        $pattern = rtrim($this->datasetPath, '/').DIRECTORY_SEPARATOR.'*.jsonl';
        $files = glob($pattern) ?: [];

        $suites = [];
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (is_string($name) && $name !== '') {
                $suites[] = $name;
            }
        }

        sort($suites);

        return $suites;
    }

    /**
     * @return array<int, EvalCase>
     */
    public function loadSuite(string $suite): array
    {
        $file = rtrim($this->datasetPath, '/').DIRECTORY_SEPARATOR.$suite.'.jsonl';
        if (! is_file($file)) {
            throw new InvalidEvalCaseException(sprintf('Suite "%s" not found at %s.', $suite, $file));
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];

        $cases = [];
        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $decoded = json_decode($trimmed, true);
            if (! is_array($decoded)) {
                throw new InvalidEvalCaseException(sprintf('%s:%d invalid JSON row.', $suite, $lineNumber));
            }

            $cases[] = $this->validator->validate($decoded, $suite, $lineNumber);
        }

        return $cases;
    }
}
