<?php

declare(strict_types=1);

namespace PhpEvals\Core\Integrations\OpenAI;

use PhpEvals\Core\Contracts\SimilarityScorer;
use PhpEvals\Core\Exceptions\RuntimeConfigurationException;

final class OpenAIEmbeddingSimilarityScorer implements SimilarityScorer
{
    private readonly OpenAIHttpTransport $transport;

    private readonly string $model;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $this->transport = new OpenAIHttpTransport($options);
        $this->model = is_string($options['model'] ?? null) && $options['model'] !== ''
            ? $options['model']
            : 'text-embedding-3-small';
    }

    public function score(string $actual, string $reference): float
    {
        if (trim($actual) === '' || trim($reference) === '') {
            return 0.0;
        }

        $response = $this->transport->post('embeddings', [
            'model' => $this->model,
            'input' => [$actual, $reference],
        ]);

        $first = $this->vector($response, 0);
        $second = $this->vector($response, 1);

        return max(0.0, min(1.0, $this->cosineSimilarity($first, $second)));
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, float>
     */
    private function vector(array $response, int $index): array
    {
        $embedding = $response['data'][$index]['embedding'] ?? null;
        if (! is_array($embedding) || $embedding === []) {
            throw new RuntimeConfigurationException(sprintf('OpenAI embeddings response is missing embedding %d.', $index));
        }

        $vector = [];
        foreach ($embedding as $value) {
            if (! is_int($value) && ! is_float($value)) {
                throw new RuntimeConfigurationException('OpenAI embeddings response contains non-numeric values.');
            }

            $vector[] = (float) $value;
        }

        return $vector;
    }

    /**
     * @param  array<int, float>  $left
     * @param  array<int, float>  $right
     */
    private function cosineSimilarity(array $left, array $right): float
    {
        if (count($left) !== count($right) || $left === []) {
            throw new RuntimeConfigurationException('Embedding vectors must be non-empty and the same length.');
        }

        $dot = 0.0;
        $leftMagnitude = 0.0;
        $rightMagnitude = 0.0;

        foreach ($left as $index => $value) {
            $other = $right[$index];
            $dot += $value * $other;
            $leftMagnitude += $value * $value;
            $rightMagnitude += $other * $other;
        }

        if ($leftMagnitude <= 0.0 || $rightMagnitude <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($leftMagnitude) * sqrt($rightMagnitude));
    }
}
