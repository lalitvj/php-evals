<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Support;

use PhpEvals\Core\Support\TextTokenizer;
use PHPUnit\Framework\TestCase;

final class TextTokenizerTest extends TestCase
{
    public function test_tokenizes_and_lowercases(): void
    {
        $tokens = TextTokenizer::tokenize('Hello World Testing');

        self::assertSame(['hello', 'world', 'testing'], $tokens);
    }

    public function test_filters_short_tokens(): void
    {
        $tokens = TextTokenizer::tokenize('I am a big cat');

        self::assertSame(['big', 'cat'], $tokens);
    }

    public function test_deduplicates_tokens(): void
    {
        $tokens = TextTokenizer::tokenize('cat cat dog cat dog');

        self::assertSame(['cat', 'dog'], $tokens);
    }

    public function test_splits_on_non_word_characters(): void
    {
        $tokens = TextTokenizer::tokenize('hello-world, foo_bar!baz');

        // \W+ does not split on underscores (they are word characters in regex)
        self::assertSame(['hello', 'world', 'foo_bar', 'baz'], $tokens);
    }

    public function test_empty_string_returns_empty_array(): void
    {
        self::assertSame([], TextTokenizer::tokenize(''));
    }

    public function test_custom_min_length(): void
    {
        $tokens = TextTokenizer::tokenize('I am a big cat here', 4);

        self::assertSame(['here'], $tokens);
    }
}
