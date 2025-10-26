# Installation Guide

This guide walks through every step of installing and configuring Laravel Packagist in your Laravel application.

## Requirements

Before installing, ensure you have:

- **PHP 8.4+** - Strict typing with readonly properties
- **Laravel 12+** - Latest Laravel framework
- **Composer** - PHP dependency manager
- **Redis or File cache** - For caching (optional but recommended)

### Checking Your Environment

```bash
# Check PHP version
php -v
# Should output: PHP 8.4.0 or higher

# Check Laravel version
php artisan --version
# Should output: Laravel Framework 12.x.x

# Check Composer version
composer --version
# Should output: Composer 2.x.x
```

## Step 1: Install via Composer

The simplest way to install the package:

```bash
composer require akira/laravel-packagist
```

This command:
1. Downloads the latest version from Packagist
2. Installs all dependencies
3. Updates `composer.lock`
4. Registers the service provider (auto-discovery)

### Verify Installation

After installation, verify the package is available:

```bash
# Check if package is installed
composer show akira/laravel-packagist
```

Expected output:
```
name     : akira/laravel-packagist
descrip. : Production-grade Laravel package for Packagist.org API integration
keywords : packagist, api, laravel, cache, php
versions : * 1.0.0
```

## Step 2: Run Installation Command

The easiest way to set up Laravel Packagist is using the install command:

```bash
php artisan packagist:install
```

This command will:
1. Publish configuration file to `config/packagist.php`
2. Show environment variable setup instructions
3. Explain queue configuration options
4. Run optional installation tests
5. Display next steps and quick start guide

### Manual Configuration (Alternative)

If you prefer to publish configuration manually:

```bash
php artisan vendor:publish --provider="Akira\Packagist\Providers\PackagistServiceProvider"
```

This creates:
- `config/packagist.php` - Main configuration file

### Verify Configuration

Check that the file was created:

```bash
test -f config/packagist.php && echo "Config published" || echo "Config missing"
```

## Step 3: (Optional) Set Up Queue for Auto-Revalidation

If you want to use auto-revalidation, configure Laravel Queue:

### Option A: Use Redis (Recommended)

**Install Redis:**

```bash
# macOS with Homebrew
brew install redis

# Start Redis server
redis-server

# Verify Redis is running
redis-cli ping
# Should output: PONG
```

**Configure Laravel:**

Update `.env`:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

### Option B: Use Database Queue

If you prefer database queue:

**Create jobs table:**

```bash
php artisan queue:table
php artisan migrate
```

**Configure Laravel:**

Update `.env`:

```env
QUEUE_CONNECTION=database
```

### Option C: Use Synchronous Queue (Testing Only)

For development/testing without external dependencies:

```env
QUEUE_CONNECTION=sync
```

️ Note: With sync, jobs execute immediately instead of background. Auto-revalidation won't actually happen in background.

### Start Queue Worker

If using Redis or Database queue:

```bash
# Start queue worker in foreground
php artisan queue:work

# Start in background with daemon mode
php artisan queue:work --daemon

# Start with specific queue name
php artisan queue:work redis --queue=low

# Multiple workers for better throughput
php artisan queue:work redis &
php artisan queue:work redis &
php artisan queue:work redis &
```

## Step 4: Configure Environment

Create or update `.env` file with sensible defaults:

```env
# Cache Configuration
PACKAGIST_CACHE_DRIVER=redis          # or 'file', 'memcached', 'database'
PACKAGIST_CACHE_TTL=28800             # 8 hours
PACKAGIST_CACHE_ENABLED=true          # Enable caching

# Auto-Revalidation Configuration
PACKAGIST_AUTO_REVALIDATION=true      # Enable background renewal
PACKAGIST_REVALIDATE_BEFORE_EXPIRY=300  # Renew 5 minutes before expiry
PACKAGIST_QUEUE=low                   # Low priority queue

# Laravel Cache Driver
CACHE_DRIVER=redis                    # Use Redis for all caching
```

### Environment Variable Reference

| Variable | Default | Description |
|----------|---------|-------------|
| `PACKAGIST_CACHE_DRIVER` | (app default) | Redis, File, Memcached, or Database |
| `PACKAGIST_CACHE_TTL` | 28800 | Cache time to live in seconds |
| `PACKAGIST_CACHE_ENABLED` | true | Enable/disable caching |
| `PACKAGIST_AUTO_REVALIDATION` | true | Enable background renewal |
| `PACKAGIST_REVALIDATE_BEFORE_EXPIRY` | 300 | Seconds before expiry to renew |
| `PACKAGIST_QUEUE` | default | Queue name for jobs |

## Step 5: Verify Installation

Create a simple test to ensure everything works:

### Method 1: Using Tinker

```bash
php artisan tinker
```

Then in tinker:

```php
use Akira\Packagist\Facades\Packagist;

// Test basic request
$package = Packagist::package('laravel/framework');
echo $package->name;        // Should output: laravel/framework
echo $package->description; // Should output: The Laravel Framework.
```

### Method 2: Using a Route

Create a test route in `routes/web.php`:

```php
Route::get('/test-packagist', function () {
    $package = Packagist::package('laravel/framework');

    return [
        'name' => $package->name,
        'description' => $package->description,
        'downloads' => $package->downloads,
    ];
});
```

Visit `http://localhost:8000/test-packagist` and you should see:

```json
{
    "name": "laravel/framework",
    "description": "The Laravel Framework.",
    "downloads": 9999999
}
```

### Method 3: Using a Test File

Create `tests/Feature/PackagistInstallationTest.php`:

```php
<?php

use Akira\Packagist\Facades\Packagist;

test('packagist can fetch package', function () {
    $package = Packagist::package('laravel/framework');

    expect($package)->toHaveProperty('name', 'laravel/framework');
    expect($package->description)->toBeString();
    expect($package->downloads)->toBeInt();
});
```

Run the test:

```bash
./vendor/bin/pest tests/Feature/PackagistInstallationTest.php
```

## Troubleshooting Installation

### Issue: Service Provider Not Registered

**Symptom:** `Error: Class 'Akira\Packagist\Facades\Packagist' not found`

**Solution:**
1. Ensure package is installed: `composer show akira/laravel-packagist`
2. Clear autoloader: `composer dump-autoload`
3. Clear Laravel cache: `php artisan cache:clear && php artisan config:clear`

### Issue: Configuration File Not Found

**Symptom:** `Error in config/packagist.php - file not found`

**Solution:**
```bash
# Publish configuration manually
php artisan vendor:publish --provider="Akira\Packagist\Providers\PackagistServiceProvider" --force
```

### Issue: Connection Refused to Packagist API

**Symptom:** `GuzzleException: cURL error 7: Failed to connect`

**Solution:**
1. Check internet connection: `ping repo.packagist.org`
2. Verify Packagist is online: `curl https://repo.packagist.org/packages.json`
3. Check firewall rules
4. Disable caching temporarily: `PACKAGIST_CACHE_ENABLED=false`

### Issue: Queue Not Processing Jobs

**Symptom:** Jobs stay in queue, not executing

**Solution:**
1. Verify queue worker is running: `ps aux | grep "queue:work"`
2. Check queue configuration: `QUEUE_CONNECTION=redis`
3. Restart queue: `php artisan queue:restart`
4. Check failed jobs: `php artisan queue:failed`
5. Retry failed jobs: `php artisan queue:retry all`

### Issue: Out of Memory

**Symptom:** `Allowed memory size exhausted`

**Solution:**
1. Increase PHP memory limit in `.env`: `PHP_MEMORY_LIMIT=512M`
2. Clear cache: `php artisan cache:clear`
3. Restart PHP/queue: `php artisan queue:restart`

## Next Steps

Now that installation is complete:

1. **[Read Configuration Guide](./03-configuration.md)** - Understand all options
2. **[Learn Cache Strategies](./04-cache-strategies.md)** - Choose best strategy
3. **[Start Using the API](./05-api-usage.md)** - Begin integrating

## Quick Reference

```bash
# Install package
composer require akira/laravel-packagist

# Run install command (recommended)
php artisan packagist:install

# Or manually publish config
php artisan vendor:publish --provider="Akira\Packagist\Providers\PackagistServiceProvider"

# Start queue (if using auto-revalidation)
php artisan queue:work redis

# Clear cache
php artisan cache:clear

# Test installation
php artisan tinker
Packagist::package('laravel/framework')
```

---

**Congratulations!** You've successfully installed Laravel Packagist. Next, explore the [Configuration Guide](./03-configuration.md).
