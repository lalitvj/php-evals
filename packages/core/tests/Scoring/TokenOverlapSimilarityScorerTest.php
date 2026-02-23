<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Scoring;

use PhpEvals\Core\Scoring\TokenOverlapSimilarityScorer;
use PHPUnit\Framework\TestCase;

final class TokenOverlapSimilarityScorerTest extends TestCase
{
    public function test_score_returns_zero_for_empty_strings(): void
    {
        $scorer = new TokenOverlapSimilarityScorer;

        self::assertSame(0.0, $scorer->score('', 'reference'));
        self::assertSame(0.0, $scorer->score('actual', ''));
    }

    public function test_score_calculates_overlap(): void
    {
        $scorer = new TokenOverlapSimilarityScorer;
        $score = $scorer->score('refund process next steps', 'refund process steps');

        self::assertGreaterThan(0.5, $score);
        self::assertLessThanOrEqual(1.0, $score);
    }
}
