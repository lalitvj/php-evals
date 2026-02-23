<?php

declare(strict_types=1);

namespace PhpEvals\Core\Contracts;

interface SimilarityScorer
{
    public function score(string $actual, string $reference): float;
}
