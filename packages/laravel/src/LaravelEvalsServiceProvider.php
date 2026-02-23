<?php

declare(strict_types=1);

namespace PhpEvals\Laravel;

use Illuminate\Support\ServiceProvider;
use PhpEvals\Core\Cli\PhpEvalsApplication;
use PhpEvals\Laravel\Commands\RunEvalsArtisanCommand;

final class LaravelEvalsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-evals.php', 'ai-evals');

        $this->app->singleton(PhpEvalsApplication::class, function ($app): PhpEvalsApplication {
            $config = $app['config']->get('ai-evals', []);
            $workingDirectory = getcwd() ?: '.';
            if (is_object($app) && method_exists($app, 'basePath')) {
                $basePath = $app->basePath();
                if (is_string($basePath) && $basePath !== '') {
                    $workingDirectory = $basePath;
                }
            }

            return new PhpEvalsApplication(is_array($config) ? $config : [], $workingDirectory);
        });
    }

    public function boot(): void
    {
        $configTarget = 'config/ai-evals.php';
        if (method_exists($this->app, 'configPath')) {
            $configTarget = $this->app->configPath('ai-evals.php');
        }

        $this->publishes([
            __DIR__.'/../config/ai-evals.php' => $configTarget,
        ], 'ai-evals-config');

        if ($this->app->runningInConsole()) {
            $this->commands([RunEvalsArtisanCommand::class]);
        }
    }
}
