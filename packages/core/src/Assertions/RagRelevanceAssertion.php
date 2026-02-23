<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Contracts\SimilarityScorer;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;

final class RagRelevanceAssertion implements Assertion
{
    public function __construct(private readonly SimilarityScorer $scorer) {}

    public function type(): string
    {
        return 'rag_relevance';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $reference = $definition['reference'] ?? ($case->metadata['reference'] ?? $case->input);
        $threshold = $definition['threshold'] ?? 0.6;

        if (! is_string($reference) || $reference === '') {
            return AssertionResult::fail($this->type(), 'rag_relevance.reference must be a non-empty string.');
        }

        if (! is_int($threshold) && ! is_float($threshold)) {
            return AssertionResult::fail($this->type(), 'rag_relevance.threshold must be numeric.');
        }

        $score = $this->scorer->score($response->output, $reference);
        if ($score < (float) $threshold) {
            return AssertionResult::fail(
                $this->type(),
                sprintf('Relevance score %.3f is lower than threshold %.3f.', $score, (float) $threshold),
                ['score' => $score],
            );
        }

        return AssertionResult::pass($this->type(), ['score' => $score]);
    }
}
