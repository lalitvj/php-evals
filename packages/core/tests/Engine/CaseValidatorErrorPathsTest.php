<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Engine;

use PhpEvals\Core\Engine\CaseValidator;
use PhpEvals\Core\Exceptions\InvalidEvalCaseException;
use PHPUnit\Framework\TestCase;

final class CaseValidatorErrorPathsTest extends TestCase
{
    public function test_missing_id_throws(): void
    {
        $this->expectException(InvalidEvalCaseException::class);
        (new CaseValidator)->validate(['input' => 'x', 'expected' => ['assertions' => []]], 'suite', 1);
    }

    public function test_missing_input_throws(): void
    {
        $this->expectException(InvalidEvalCaseException::class);
        (new CaseValidator)->validate(['id' => 'x', 'expected' => ['assertions' => []]], 'suite', 1);
    }

    public function test_missing_expected_throws(): void
    {
        $this->expectException(InvalidEvalCaseException::class);
        (new CaseValidator)->validate(['id' => 'x', 'input' => 'test'], 'suite', 1);
    }
}
