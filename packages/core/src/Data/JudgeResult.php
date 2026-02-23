<?php

declare(strict_types=1);

namespace PhpEvals\Core\Data;

final class JudgeResult
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly float $score,
        public readonly ?string $reason = null,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly float $cost = 0.0,
        public readonly array $details = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'reason' => $this->reason,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'cost' => $this->cost,
            'details' => $this->details,
        ];
    }
}
