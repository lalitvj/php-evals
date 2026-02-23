<?php

declare(strict_types=1);

namespace PhpEvals\Core\Support;

final class TextTokenizer
{
    /**
     * Split text into unique, lowercased word tokens of at least the given minimum length.
     *
     * @return array<int, string>
     */
    public static function tokenize(string $text, int $minLength = 3): array
    {
        return array_values(
            array_filter(
                array_unique(preg_split('/\W+/', strtolower($text)) ?: []),
                static fn (string $token): bool => strlen($token) >= $minLength,
            ),
        );
    }
}
