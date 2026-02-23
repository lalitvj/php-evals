<?php

declare(strict_types=1);

namespace PhpEvals\Core\Engine;

use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Exceptions\InvalidEvalCaseException;

final class CaseValidator
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function validate(array $row, string $suite, int $line): EvalCase
    {
        $id = $row['id'] ?? null;
        $input = $row['input'] ?? null;
        $expected = $row['expected'] ?? null;
        $metadata = $row['metadata'] ?? [];

        if (! is_string($id) || $id === '') {
            throw new InvalidEvalCaseException(sprintf('%s:%d missing valid "id".', $suite, $line));
        }

        if (! is_string($input)) {
            throw new InvalidEvalCaseException(sprintf('%s:%d missing valid "input".', $suite, $line));
        }

        if (! is_array($expected)) {
            throw new InvalidEvalCaseException(sprintf('%s:%d missing valid "expected" object.', $suite, $line));
        }

        if (! is_array($metadata)) {
            throw new InvalidEvalCaseException(sprintf('%s:%d invalid "metadata" object.', $suite, $line));
        }

        $assertions = $expected['assertions'] ?? null;
        if (! is_array($assertions)) {
            throw new InvalidEvalCaseException(sprintf('%s:%d expected.assertions must be an array.', $suite, $line));
        }

        return new EvalCase($id, $input, $expected, $metadata);
    }
}
