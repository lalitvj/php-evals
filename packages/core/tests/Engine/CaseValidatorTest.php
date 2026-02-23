<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Engine;

use PhpEvals\Core\Engine\CaseValidator;
use PhpEvals\Core\Exceptions\InvalidEvalCaseException;
use PHPUnit\Framework\TestCase;

final class CaseValidatorTest extends TestCase
{
    public function test_validator_returns_eval_case_for_valid_input(): void
    {
        $validator = new CaseValidator;
        $case = $validator->validate(
            ['id' => 'case-1', 'input' => 'hello', 'expected' => ['assertions' => []]],
            'suite',
            1,
        );

        self::assertSame('case-1', $case->id);
    }

    public function test_validator_requires_metadata_if_provided(): void
    {
        $validator = new CaseValidator;

        $this->expectException(InvalidEvalCaseException::class);
        $validator->validate(
            ['id' => 'case-1', 'input' => 'hello', 'expected' => ['assertions' => []], 'metadata' => 'bad'],
            'suite',
            1,
        );
    }

    public function test_validator_rejects_invalid_assertions(): void
    {
        $validator = new CaseValidator;

        $this->expectException(InvalidEvalCaseException::class);
        $validator->validate(
            ['id' => 'case-1', 'input' => 'hello', 'expected' => ['assertions' => 'bad']],
            'suite',
            1,
        );
    }
}
