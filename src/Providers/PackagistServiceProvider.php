<?php

declare(strict_types=1);

namespace Akira\Packagist\Providers;

use Akira\Packagist\Commands\InstallCommand;
use Akira\Packagist\PackagistManager;
use Illuminate\Support\ServiceProvider;

final class PackagistServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/packagist.php',
            'packagist'
        );

        $this->app->singleton(PackagistManager::class, function (): PackagistManager {
            return new PackagistManager(
                config('packagist', [])
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/packagist.php' => config_path('packagist.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
