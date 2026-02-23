<?php

declare(strict_types=1);

namespace PhpEvals\Core\Scoring;

use PhpEvals\Core\Contracts\SimilarityScorer;

final class TokenOverlapSimilarityScorer implements SimilarityScorer
{
    public function score(string $actual, string $reference): float
    {
        $actualTokens = $this->tokens($actual);
        $referenceTokens = $this->tokens($reference);

        if ($actualTokens === [] || $referenceTokens === []) {
            return 0.0;
        }

        $intersection = array_intersect($actualTokens, $referenceTokens);
        $union = array_unique(array_merge($actualTokens, $referenceTokens));

        return count($intersection) / count($union);
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $value): array
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return [];
        }

        $parts = preg_split('/\s+/', $normalized);

        return array_values(array_filter($parts ?: [], static fn (string $token): bool => $token !== ''));
    }
}
