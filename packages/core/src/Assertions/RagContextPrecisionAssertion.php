<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Support\TextTokenizer;

final class RagContextPrecisionAssertion implements Assertion
{
    public function type(): string
    {
        return 'rag_context_precision';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $threshold = $definition['threshold'] ?? 0.5;
        if (! is_int($threshold) && ! is_float($threshold)) {
            return AssertionResult::fail($this->type(), 'rag_context_precision.threshold must be numeric.');
        }

        $context = $definition['context'] ?? ($case->metadata['context'] ?? null);
        if (is_string($context)) {
            $context = [$context];
        }
        if (! is_array($context) || $context === []) {
            return AssertionResult::fail($this->type(), 'rag_context_precision requires context in assertion or case metadata.');
        }

        $queryTokens = TextTokenizer::tokenize($case->input);
        if ($queryTokens === []) {
            return AssertionResult::fail($this->type(), 'Case input is empty, cannot score context precision.');
        }

        $relevantCount = 0;
        $responseTokens = TextTokenizer::tokenize($response->output);
        foreach ($context as $chunk) {
            if (! is_string($chunk) || $chunk === '') {
                continue;
            }

            $chunkLower = strtolower($chunk);
            $queryHit = false;
            foreach ($queryTokens as $token) {
                if (str_contains($chunkLower, $token)) {
                    $queryHit = true;
                    break;
                }
            }

            if (! $queryHit) {
                continue;
            }

            foreach ($responseTokens as $token) {
                if (str_contains($chunkLower, $token)) {
                    $relevantCount++;
                    break;
                }
            }
        }

        $score = $relevantCount / max(1, count($context));
        if ($score < (float) $threshold) {
            return AssertionResult::fail(
                $this->type(),
                sprintf('Context precision score %.3f is lower than threshold %.3f.', $score, (float) $threshold),
                ['score' => $score],
            );
        }

        return AssertionResult::pass($this->type(), ['score' => $score]);
    }
}
