<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Contracts\SimilarityScorer;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;

final class SemanticSimilarityAssertion implements Assertion
{
    public function __construct(private readonly SimilarityScorer $scorer) {}

    public function type(): string
    {
        return 'semantic_similarity';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $reference = $definition['reference'] ?? null;
        $threshold = $definition['threshold'] ?? 0.7;

        if (! is_string($reference) || $reference === '') {
            return AssertionResult::fail($this->type(), 'semantic_similarity.reference must be a non-empty string.');
        }

        if (! is_int($threshold) && ! is_float($threshold)) {
            return AssertionResult::fail($this->type(), 'semantic_similarity.threshold must be numeric.');
        }

        $score = $this->scorer->score($response->output, $reference);
        if ($score < (float) $threshold) {
            return AssertionResult::fail(
                $this->type(),
                sprintf('Similarity %.3f is lower than threshold %.3f.', $score, (float) $threshold),
                ['score' => $score],
            );
        }

        return AssertionResult::pass($this->type());
    }
}
