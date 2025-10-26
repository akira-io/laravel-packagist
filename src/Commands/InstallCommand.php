<?php

declare(strict_types=1);

namespace Akira\Packagist\Commands;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'packagist:install';

    protected $description = 'Install Laravel Packagist and publish configuration';

    public function handle(): int
    {
        $this->info('Installing Laravel Packagist...');
        $this->newLine();
        
        $this->info('Step 1: Publishing configuration file...');
        $this->call('vendor:publish', [
            '--provider' => 'Akira\Packagist\Providers\PackagistServiceProvider',
            '--force' => false,
        ]);
        $this->line('  <fg=green>✓</> Configuration published to config/packagist.php');
        $this->newLine();
        
        $this->info('Step 2: Environment configuration (optional)');
        $this->line('Add these to your .env file (optional):');
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
        $this->newLine();
        
        $this->info('Step 3: Queue configuration (for auto-revalidation)');
        $this->line('If using auto-revalidation, ensure queue is configured:');
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
        $this->newLine();
        
        $this->info('Step 4: Testing installation');
        if ($this->confirm('Run quick test?', true)) {
            $this->testInstallation();
        }
        $this->newLine();
        
        $this->info('Installation complete!');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Review config/packagist.php');
        $this->line('  2. Configure environment variables if needed');
        $this->line('  3. Start queue worker if using auto-revalidation');
        $this->line('  4. Read documentation: docs/00-toc.md');
        $this->newLine();
        $this->line('Quick test:');
        $this->line('  php artisan tinker');
        $this->line('  Packagist::package("laravel/framework")');
        $this->newLine();

        return self::SUCCESS;
    }

    private function testInstallation(): void
    {
        try {
            $this->newLine();
            $this->line('Testing configuration...');
            
            if (!class_exists('Akira\Packagist\Facades\Packagist')) {
                $this->error('  Packagist facade not found');
                return;
            }
            $this->line('  <fg=green>✓</> Packagist facade accessible');
            
            $config = config('packagist');
            if (!$config) {
                $this->error('  Configuration not found');
                return;
            }
            $this->line('  <fg=green>✓</> Configuration loaded');
            
            if (config('packagist.use.enabled')) {
                $this->line('  <fg=green>✓</> Caching enabled');
            } else {
                $this->line('  <fg=yellow>!</> Caching disabled');
            }
            
            if (config('packagist.auto_revalidation.enabled')) {
                $this->line('  <fg=green>✓</> Auto-revalidation enabled');
            } else {
                $this->line('  <fg=yellow>!</> Auto-revalidation disabled');
            }

            $this->line('  <fg=green>✓</> All checks passed!');
        } catch (\Exception $e) {
            $this->error('  Test failed: ' . $e->getMessage());
        }
    }
}
