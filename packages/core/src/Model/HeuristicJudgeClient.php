<?php

declare(strict_types=1);

namespace PhpEvals\Core\Model;

use PhpEvals\Core\Contracts\JudgeClient;
use PhpEvals\Core\Data\EvalCase;
use PhpEvals\Core\Data\JudgeResult;
use PhpEvals\Core\Data\ModelResponse;

final class HeuristicJudgeClient implements JudgeClient
{
    public function judge(EvalCase $case, ModelResponse $response, array $definition): JudgeResult
    {
        $rubric = is_string($definition['rubric'] ?? null) ? strtolower($definition['rubric']) : '';
        $output = strtolower($response->output);

        if ($rubric === '') {
            return new JudgeResult(0.0, 'Rubric is empty.');
        }

        $keywords = array_values(array_filter(array_unique(preg_split('/\W+/', $rubric) ?: []), static fn (string $token): bool => strlen($token) >= 4));
        if ($keywords === []) {
            return new JudgeResult(0.0, 'Rubric has no scorable keywords.');
        }

        $hits = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($output, $keyword)) {
                $hits++;
            }
        }

        $score = $hits / count($keywords);

        return new JudgeResult(
            max(0.0, min(1.0, $score)),
            sprintf('Matched %d/%d rubric keywords.', $hits, count($keywords)),
            0,
            0,
            0.0,
            ['matched_keywords' => $hits, 'total_keywords' => count($keywords)],
        );
    }
}
