<?php

declare(strict_types=1);

namespace PhpEvals\Laravel;

use Illuminate\Support\ServiceProvider;
use PhpEvals\Core\Cli\PhpEvalsApplication;
use PhpEvals\Laravel\Commands\CompareEvalsArtisanCommand;
use PhpEvals\Laravel\Commands\InitEvalsArtisanCommand;
use PhpEvals\Laravel\Commands\QueueEvalsArtisanCommand;
use PhpEvals\Laravel\Commands\RunEvalsArtisanCommand;
use PhpEvals\Laravel\Commands\ShowEvalRunProgressArtisanCommand;

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

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/ai-evals.php' => $configTarget,
        ], 'ai-evals-config');

        if (method_exists($this, 'publishesMigrations')) {
            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ]);
        } else {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'ai-evals-migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                RunEvalsArtisanCommand::class,
                CompareEvalsArtisanCommand::class,
                InitEvalsArtisanCommand::class,
                QueueEvalsArtisanCommand::class,
                ShowEvalRunProgressArtisanCommand::class,
            ]);
        }
    }
}
