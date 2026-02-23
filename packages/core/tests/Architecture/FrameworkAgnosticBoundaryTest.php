<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class FrameworkAgnosticBoundaryTest extends TestCase
{
    public function test_core_source_does_not_reference_framework_namespaces(): void
    {
        $repoRoot = dirname(__DIR__, 4);
        $coreSourcePath = $repoRoot.'/packages/core/src';
        $forbiddenNamespaces = [
            'Illuminate\\',
            'Laravel\\',
            'Symfony\\Bundle\\FrameworkBundle',
        ];

        $violations = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($coreSourcePath));

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($fileInfo->getPathname());
            if (! is_string($contents)) {
                continue;
            }

            foreach ($forbiddenNamespaces as $namespace) {
                if (str_contains($contents, $namespace)) {
                    $violations[] = sprintf('%s references forbidden namespace "%s".', $fileInfo->getPathname(), $namespace);
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_core_package_manifest_has_no_framework_runtime_dependencies(): void
    {
        $repoRoot = dirname(__DIR__, 4);
        $manifestPath = $repoRoot.'/packages/core/composer.json';
        $manifestContents = file_get_contents($manifestPath);

        self::assertIsString($manifestContents);

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode($manifestContents, true, 512, JSON_THROW_ON_ERROR);
        /** @var array<string, string> $require */
        $require = is_array($manifest['require'] ?? null) ? $manifest['require'] : [];

        $forbiddenPackages = [
            'laravel/framework',
            'illuminate/support',
            'illuminate/console',
            'symfony/framework-bundle',
        ];

        $frameworkDependencies = array_values(array_intersect(array_keys($require), $forbiddenPackages));

        self::assertSame([], $frameworkDependencies, sprintf(
            'Core package must stay framework agnostic. Found forbidden dependencies: %s',
            implode(', ', $frameworkDependencies),
        ));
    }
}
