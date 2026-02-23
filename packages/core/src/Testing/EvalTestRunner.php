<?php

declare(strict_types=1);

namespace PhpEvals\Core\Testing;

use PhpEvals\Core\Cli\PhpEvalsApplication;

final class EvalTestRunner
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function runSuite(string $suite, array $config = [], ?string $workingDirectory = null): int
    {
        $app = new PhpEvalsApplication($config, $workingDirectory);

        return $app->run([
            'php-evals',
            '--suite='.$suite,
        ], static function (): void {});
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{code:int, output:string}
     */
    public static function runAndCollect(string $suite, array $config = [], ?string $workingDirectory = null): array
    {
        $app = new PhpEvalsApplication($config, $workingDirectory);
        $lines = [];

        $code = $app->run([
            'php-evals',
            '--suite='.$suite,
        ], static function (string $line) use (&$lines): void {
            $lines[] = $line;
        });

        return [
            'code' => $code,
            'output' => trim(implode(PHP_EOL, $lines)),
        ];
    }
}
