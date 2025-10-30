<?php

declare(strict_types=1);

namespace Akira\Packagist\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;

final class InstallCommand extends Command
{
    protected $signature = 'packagist:install';

    protected $description = 'Install Laravel Packagist and publish configuration';

    public function handle(): int
    {
        intro('Laravel Packagist Installation');

        info('Step 1: Publishing configuration file');
        $this->call('vendor:publish', [
            '--provider' => \Akira\Packagist\Providers\PackagistServiceProvider::class,
            '--force' => false,
        ]);
        info('Configuration published to config/packagist.php');

        info('Step 2: Environment configuration (optional)');
        info('Add these to your .env file:');

        $envVariables = 'PACKAGIST_CACHE_DRIVER=redis          # Cache driver (redis, file, memcached, database)'.PHP_EOL.
            'PACKAGIST_CACHE_TTL=28800             # Cache time to live in seconds (8 hours)'.PHP_EOL.
            'PACKAGIST_CACHE_ENABLED=true          # Enable/disable caching'.PHP_EOL.
            'PACKAGIST_AUTO_REVALIDATION=true      # Enable background cache refresh'.PHP_EOL.
            'PACKAGIST_REVALIDATE_BEFORE_EXPIRY=300 # Seconds before expiry to refresh'.PHP_EOL.
            'PACKAGIST_QUEUE=low                   # Queue name for auto-revalidation jobs';

        note($envVariables);

        info('Step 3: Queue configuration (for auto-revalidation)');
        info('If using auto-revalidation, ensure queue is configured:');

        $queueConfig = 'For Redis queue:'.PHP_EOL.
            '  QUEUE_CONNECTION=redis'.PHP_EOL.PHP_EOL.
            'For Database queue:'.PHP_EOL.
            '  php artisan queue:table && php artisan migrate'.PHP_EOL.
            '  QUEUE_CONNECTION=database'.PHP_EOL.PHP_EOL.
            'Start queue worker:'.PHP_EOL.
            '  php artisan queue:work redis --queue=low';

        note($queueConfig);

        info('Step 4: Testing installation');
        if (confirm('Run quick test?')) {
            $this->testInstallation();
        }

        info('Step 5: Support the project - Give a star on GitHub');
        if (confirm('Open repository in browser?')) {
            $this->openGitHub();
        }

        outro('Installation complete!');

        $nextSteps = 'Next steps:'.PHP_EOL.
            '  1. Review config/packagist.php'.PHP_EOL.
            '  2. Configure environment variables if needed'.PHP_EOL.
            '  3. Start queue worker if using auto-revalidation'.PHP_EOL.
            '  4. Read documentation: docs/00-toc.md'.PHP_EOL.PHP_EOL.
            'Quick test:'.PHP_EOL.
            '  php artisan tinker'.PHP_EOL.
            '  Packagist::package("laravel/framework")';

        note($nextSteps);

        return self::SUCCESS;
    }

    private function testInstallation(): void
    {
        try {
            info('Testing configuration...');

            if (! class_exists(\Akira\Packagist\Facades\Packagist::class)) {
                info('Packagist facade not found');

                return;
            }
            info('Packagist facade accessible');

            $config = config('packagist');
            if (! $config) {
                info('Configuration not found');

                return;
            }
            info('Configuration loaded');

            if (config('packagist.use.enabled')) {
                info('Caching enabled');
            } else {
                info('Caching disabled (configure in .env)');
            }

            if (config('packagist.auto_revalidation.enabled')) {
                info('Auto-revalidation enabled');
            } else {
                info('Auto-revalidation disabled (configure in .env)');
            }

            info('All checks passed!');
        } catch (\Exception $e) {
            info('Test failed: '.$e->getMessage());
        }
    }

    private function openGitHub(): void
    {
        $url = 'https://github.com/akira-io/laravel-packagist';
        $os = PHP_OS_FAMILY;

        try {
            match ($os) {
                'Darwin' => shell_exec("open '{$url}'"),
                'Linux' => shell_exec("xdg-open '{$url}'"),
                'Windows' => shell_exec("start '{$url}'"),
                default => null,
            };
            info("Opening {$url}");
        } catch (\Exception) {
            info("Visit: {$url}");
        }
    }
}
