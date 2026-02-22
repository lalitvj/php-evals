<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Tests;

use PhpEvals\Laravel\BridgeIdentity;
use PHPUnit\Framework\TestCase;

final class BridgeIdentityTest extends TestCase
{
    public function test_laravel_bridge_identity_name_is_stable(): void
    {
        self::assertSame('laravel-bridge', BridgeIdentity::name());
    }
}
