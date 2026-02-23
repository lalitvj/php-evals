<?php

declare(strict_types=1);

namespace PhpEvals\Core\Assertions;

use PhpEvals\Core\Contracts\Assertion;
use PhpEvals\Core\Data\AssertionResult;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\ModelResponse;

final class RagFaithfulnessAssertion implements Assertion
{
    public function type(): string
    {
        return 'rag_faithfulness';
    }

    public function evaluate(EvalCase $case, ModelResponse $response, array $definition): AssertionResult
    {
        $threshold = $definition['threshold'] ?? 0.6;
        if (! is_int($threshold) && ! is_float($threshold)) {
            return AssertionResult::fail($this->type(), 'rag_faithfulness.threshold must be numeric.');
        }

        $context = $definition['context'] ?? ($case->metadata['context'] ?? null);
        if (is_string($context)) {
            $context = [$context];
        }
        if (! is_array($context) || $context === []) {
            return AssertionResult::fail($this->type(), 'rag_faithfulness requires context in assertion or case metadata.');
        }

        $contextText = strtolower(implode(' ', array_filter(array_map(static fn (mixed $entry): string => is_string($entry) ? $entry : '', $context))));
        $outputTokens = $this->tokens($response->output);

        if ($outputTokens === []) {
            return AssertionResult::fail($this->type(), 'Response output is empty, cannot score faithfulness.');
        }

        $supported = 0;
        foreach ($outputTokens as $token) {
            if (str_contains($contextText, $token)) {
                $supported++;
            }
        }

        $score = $supported / count($outputTokens);

        if ($score < (float) $threshold) {
            return AssertionResult::fail(
                $this->type(),
                sprintf('Faithfulness score %.3f is lower than threshold %.3f.', $score, (float) $threshold),
                ['score' => $score],
            );
        }

        return AssertionResult::pass($this->type(), ['score' => $score]);
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $text): array
    {
        return array_values(array_filter(array_unique(preg_split('/\W+/', strtolower($text)) ?: []), static fn (string $token): bool => strlen($token) >= 3));
    }
}
