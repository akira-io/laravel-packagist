# Getting Started with Laravel Packagist

Welcome to **Laravel Packagist** - a powerful, production-ready PHP package for integrating with the Packagist.org API. This guide will help you get up and running in minutes.

## What is Laravel Packagist?

Laravel Packagist is a Laravel package that provides:

- **Simple API Integration**: Easy-to-use methods to interact with Packagist.org
- **Intelligent Caching**: Multiple cache strategies to optimize performance
- **Auto-Revalidation**: Automatic background cache refresh before expiry
- **Type Safety**: Full PHP 8.4 strict typing and static analysis support
- **Developer Friendly**: Clean architecture with DTOs, Actions, and Validators

## Key Features

###  Performance Optimized
- Multiple cache strategies (Remember, Forever, Revalidate, None)
- Background automatic cache renewal before expiry
- Zero latency for cached requests

###  Type Safe
- PHP 8.4 strict typing with readonly properties
- PHPStan level max analysis
- Full docblock annotations for IDE support

###  Well Structured
- Action pattern for clean separation of concerns
- Data Transfer Objects (DTOs) for type-safe data
- Validators for input validation
- Service provider for Laravel integration

###  Production Ready
- Comprehensive test suite with PestPHP
- Error handling and logging
- Graceful degradation when queue unavailable

## Quick Start

### 1. Installation

```bash
composer require akira/laravel-packagist
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="Akira\Packagist\Providers\PackagistServiceProvider"
```

This creates `config/packagist.php` with sensible defaults.

### 3. Basic Usage

```php
use Akira\Packagist\Facades\Packagist;

// Get package information
$package = Packagist::package('laravel/framework');
echo $package->name;        // laravel/framework
echo $package->description; // The Laravel Framework.
echo $package->downloads;   // Number of downloads

// Search for packages
$results = Packagist::search('laravel middleware');
foreach ($results as $result) {
    echo $result['name'];
    echo $result['description'];
}

// Get Packagist statistics
$stats = Packagist::stats();
echo $stats['packages'];  // Total packages
echo $stats['downloads'];  // Total downloads
```

## Core Concepts

### Actions

Actions encapsulate specific operations. Each action handles validation, caching, and API calls:

```php
// Example: GetPackageAction
- Validates package name format
- Checks cache
- Makes API request if needed
- Returns PackageDTO with type safety
```

### DTOs (Data Transfer Objects)

DTOs provide type-safe data containers:

```php
$package = Packagist::package('symfony/console');

// Type-safe access with IDE autocomplete
echo $package->name;        // string
echo $package->description; // string
echo $package->downloads;   // int
echo $package->maintainers; // array<int, MaintainerDTO>
echo $package->versions;    // array<string, VersionDTO>
```

### Cache Strategies

Choose the best strategy for your use case:

| Strategy | TTL | Use Case |
|----------|-----|----------|
| **Revalidate** | 8 hours (default) | Most APIs - auto-renews before expiry |
| **Remember** | 1 hour | Short-lived data that needs control |
| **Forever** | Indefinite | Rarely changing data (package details) |
| **None** | N/A | Debug mode - always fetch fresh |

## Configuration Overview

The package is configured via `config/packagist.php`:

```php
return [
    'use' => [
        'driver' => env('PACKAGIST_CACHE_DRIVER'),      // Cache driver
        'strategy' => RevalidateCache::class,            // Cache strategy
        'ttl' => env('PACKAGIST_CACHE_TTL', 28800),      // 8 hours
        'tags' => ['packagist'],                         // Cache tags
        'enabled' => env('PACKAGIST_CACHE_ENABLED', true) // Enable/disable
    ],

    'auto_revalidation' => [
        'enabled' => env('PACKAGIST_AUTO_REVALIDATION', true),
        'revalidate_before_expiry' => env('PACKAGIST_REVALIDATE_BEFORE_EXPIRY', 300),
        'queue' => env('PACKAGIST_QUEUE', 'default'),
    ],

    'per_action' => [
        'GetPackageAction' => [
            'strategy' => ForeverCache::class,
        ],
        'SearchPackagesAction' => [
            'strategy' => RememberCache::class,
            'ttl' => 300, // 5 minutes
        ],
    ],
];
```

## Environment Setup

Create a `.env` file entry to customize behavior:

```env
# Cache Configuration
PACKAGIST_CACHE_DRIVER=redis
PACKAGIST_CACHE_TTL=28800
PACKAGIST_CACHE_ENABLED=true

# Auto-Revalidation
PACKAGIST_AUTO_REVALIDATION=true
PACKAGIST_REVALIDATE_BEFORE_EXPIRY=300
PACKAGIST_QUEUE=low

# Laravel Queue
QUEUE_CONNECTION=redis
```

## Next Steps

- **[Installation Guide](./02-installation.md)** - Detailed setup instructions
- **[Configuration Guide](./03-configuration.md)** - Understand all config options
- **[Cache Strategies](./04-cache-strategies.md)** - Deep dive into caching
- **[API Usage](./05-api-usage.md)** - Complete API reference with examples
- **[Auto-Revalidation](./06-auto-revalidation.md)** - Background cache refresh

## Common Questions

**Q: Do I need a queue configured?**
A: No, but auto-revalidation requires one. Without it, auto-revalidation gracefully disables itself.

**Q: Can I use a different cache driver?**
A: Yes! Set `PACKAGIST_CACHE_DRIVER=redis` (or file, memcached, database).

**Q: How do I disable caching?**
A: Set `PACKAGIST_CACHE_ENABLED=false` in `.env`.

**Q: Is this package thread-safe?**
A: Yes, all properties are readonly and immutable by design.

## Support

For issues, questions, or contributions, visit the [GitHub repository](https://github.com/akira/laravel-packagist).

---

**Ready to dive deeper?** Continue with the [Installation Guide](./02-installation.md).
