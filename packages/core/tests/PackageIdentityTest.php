<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests;

use PhpEvals\Core\PackageIdentity;
use PHPUnit\Framework\TestCase;

final class PackageIdentityTest extends TestCase
{
    public function test_core_package_identity_name_is_stable(): void
    {
        self::assertSame('core', PackageIdentity::name());
    }
}
