<?php

declare(strict_types=1);

namespace PhpEvals\Core\Engine;

use PhpEvals\Core\Contracts\Assertion;

final class AssertionRegistry
{
    /**
     * @param  array<int, Assertion>  $assertions
     */
    public function __construct(array $assertions)
    {
        foreach ($assertions as $assertion) {
            $this->assertions[$assertion->type()] = $assertion;
        }
    }

    /**
     * @var array<string, Assertion>
     */
    private array $assertions = [];

    public function get(string $type): ?Assertion
    {
        return $this->assertions[$type] ?? null;
    }
}
