<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Engine;

use PhpEvals\Core\Engine\RunComparator;
use PHPUnit\Framework\TestCase;

final class RunComparatorTest extends TestCase
{
    public function test_detects_threshold_violations_and_diffs(): void
    {
        $comparator = new RunComparator;
        $baseline = [
            'id' => 'baseline',
            'summary' => [
                'total_cases' => 2,
                'passed_cases' => 2,
                'average_score' => 0.95,
                'average_case_latency_ms' => 40,
                'total_cost' => 0.01,
            ],
            'suites' => [[
                'cases' => [
                    ['id' => 'case-1', 'passed' => true, 'score' => 1.0, 'response' => ['output' => 'good']],
                    ['id' => 'case-2', 'passed' => true, 'score' => 0.9, 'response' => ['output' => 'good']],
                ],
            ]],
        ];

        $candidate = [
            'id' => 'candidate',
            'summary' => [
                'total_cases' => 2,
                'passed_cases' => 1,
                'average_score' => 0.6,
                'average_case_latency_ms' => 80,
                'total_cost' => 0.02,
            ],
            'suites' => [[
                'cases' => [
                    ['id' => 'case-1', 'passed' => false, 'score' => 0.2, 'response' => ['output' => 'bad']],
                    ['id' => 'case-2', 'passed' => true, 'score' => 1.0, 'response' => ['output' => 'good']],
                ],
            ]],
        ];

        $result = $comparator->compare($baseline, $candidate, [
            'pass_rate_drop' => 0.1,
            'avg_score_drop' => 0.1,
        ]);

        self::assertFalse($result['passed']);
        self::assertNotEmpty($result['violations']);
        self::assertNotEmpty($result['diffs']);
    }
}
