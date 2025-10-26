<?php

declare(strict_types=1);

namespace Akira\Packagist\Providers;

use Akira\Packagist\Commands\InstallCommand;
use Akira\Packagist\PackagistManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class PackagistServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-packagist')
            ->hasConfigFile('packagist');
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(PackagistManager::class, function (): PackagistManager {
            return new PackagistManager(
                config('packagist', [])
            );
        });
    }

    public function bootingPackage(): void
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
