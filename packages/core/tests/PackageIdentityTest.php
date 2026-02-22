<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests;

use PhpEvals\Core\PackageIdentity;
use PHPUnit\Framework\TestCase;

final class PackageIdentityTest extends TestCase
{
    public function testCorePackageIdentityNameIsStable(): void
    {
        self::assertSame('core', PackageIdentity::name());
    }
}
