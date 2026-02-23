<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Reporting;

use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\CaseResult;
use PhpEvals\Core\Data\SuiteResult;
use PhpEvals\Core\Reporting\ConsoleReporter;
use PhpEvals\Core\Reporting\JsonReporter;
use PHPUnit\Framework\TestCase;

final class ReportersTest extends TestCase
{
    public function test_console_reporter_renders_failure_details(): void
    {
        $suite = new SuiteResult(
            'payments',
            [new CaseResult('case-1', [AssertionResult::fail('contains', 'Missing refund')])],
            2.5,
        );

        $output = (new ConsoleReporter)->render($suite);

        self::assertStringContainsString('Suite: payments', $output);
        self::assertStringContainsString('Missing refund', $output);
    }

    public function test_json_reporter_renders_machine_readable_payload(): void
    {
        $suite = new SuiteResult('payments', [new CaseResult('case-1', [AssertionResult::pass('contains')])], 1.1);
        $output = (new JsonReporter)->render($suite);

        $decoded = json_decode($output, true);

        self::assertIsArray($decoded);
        self::assertSame('payments', $decoded['suite']);
        self::assertSame(1, $decoded['passed_cases']);
    }
}
