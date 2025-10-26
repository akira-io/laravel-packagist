<?php

declare(strict_types=1);

namespace Akira\Packagist\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\section;

final class InstallCommand extends Command
{
    protected $signature = 'packagist:install';

    protected $description = 'Install Laravel Packagist and publish configuration';

    public function handle(): int
    {
        intro('Laravel Packagist Installation');

        // Step 1: Publish configuration
        section('Step 1: Publishing configuration file');
        $this->call('vendor:publish', [
            '--provider' => 'Akira\Packagist\Providers\PackagistServiceProvider',
            '--force' => false,
        ]);
        info('Configuration published to config/packagist.php');

        // Step 2: Show environment setup
        section('Step 2: Environment configuration (optional)');
        info('Add these to your .env file:');
        $this->newLine();
        $this->table(
            ['Variable', 'Default', 'Description'],
            [
                ['PACKAGIST_CACHE_DRIVER', 'redis', 'Cache driver (redis, file, memcached, database)'],
                ['PACKAGIST_CACHE_TTL', '28800', 'Cache time to live in seconds'],
                ['PACKAGIST_CACHE_ENABLED', 'true', 'Enable/disable caching'],
                ['PACKAGIST_AUTO_REVALIDATION', 'true', 'Enable background cache refresh'],
                ['PACKAGIST_REVALIDATE_BEFORE_EXPIRY', '300', 'Seconds before expiry to refresh'],
                ['PACKAGIST_QUEUE', 'low', 'Queue name for auto-revalidation jobs'],
            ]
        );

        // Step 3: Queue configuration
        section('Step 3: Queue configuration (for auto-revalidation)');
        info('If using auto-revalidation, ensure queue is configured:');
        $this->newLine();
        $this->line('  For Redis queue:');
        $this->line('    QUEUE_CONNECTION=redis');
        $this->newLine();
        $this->line('  For Database queue:');
        $this->line('    php artisan queue:table && php artisan migrate');
        $this->line('    QUEUE_CONNECTION=database');
        $this->newLine();
        $this->line('  Start queue worker:');
        $this->line('    php artisan queue:work redis --queue=low');

        // Step 4: Test installation
        section('Step 4: Testing installation');
        if (confirm('Run quick test?', default: true)) {
            $this->testInstallation();
        }

        // Step 5: GitHub star
        section('Step 5: Support the project');
        if (confirm('Star us on GitHub? Open repository in browser?', default: true)) {
            $this->openGitHub();
        }

        // Success message
        outro('Installation complete!');
        info('Next steps:');
        $this->newLine();
        $this->line('  1. Review config/packagist.php');
        $this->line('  2. Configure environment variables if needed');
        $this->line('  3. Start queue worker if using auto-revalidation');
        $this->line('  4. Read documentation: docs/00-toc.md');
        $this->newLine();
        info('Quick test:');
        $this->line('  php artisan tinker');
        $this->line('  Packagist::package("laravel/framework")');
        $this->newLine();

        return self::SUCCESS;
    }

    private function testInstallation(): void
    {
        try {
            info('Testing configuration...');

            // Check if facade is accessible
            if (!class_exists('Akira\Packagist\Facades\Packagist')) {
                $this->error('Packagist facade not found');
                return;
            }
            $this->line('  Packagist facade accessible');

            // Check if configuration is available
            $config = config('packagist');
            if (!$config) {
                $this->error('Configuration not found');
                return;
            }
            $this->line('  Configuration loaded');

            // Check cache configuration
            if (config('packagist.use.enabled')) {
                $this->line('  Caching enabled');
            } else {
                $this->line('  Caching disabled (configure in .env)');
            }

            // Check auto-revalidation
            if (config('packagist.auto_revalidation.enabled')) {
                $this->line('  Auto-revalidation enabled');
            } else {
                $this->line('  Auto-revalidation disabled (configure in .env)');
            }

            info('All checks passed!');
        } catch (\Exception $e) {
            $this->error('Test failed: ' . $e->getMessage());
        }
    }

    private function openGitHub(): void
    {
        $url = 'https://github.com/akira/laravel-packagist';
        $os = PHP_OS_FAMILY;

        try {
            match ($os) {
                'Darwin' => shell_exec("open '{$url}'"),
                'Linux' => shell_exec("xdg-open '{$url}'"),
                'Windows' => shell_exec("start '{$url}'"),
            };
            info("Opening {$url}");
        } catch (\Exception $e) {
            $this->line("Visit: {$url}");
        }
    }
}
