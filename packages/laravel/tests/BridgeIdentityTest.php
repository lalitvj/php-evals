<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Tests;

use PhpEvals\Laravel\BridgeIdentity;
use PHPUnit\Framework\TestCase;

final class BridgeIdentityTest extends TestCase
{
    public function testLaravelBridgeIdentityNameIsStable(): void
    {
        self::assertSame('laravel-bridge', BridgeIdentity::name());
    }
}
