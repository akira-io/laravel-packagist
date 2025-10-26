# Laravel Packagist SDK

A modular SDK and Laravel integration for the [Packagist.org](https://packagist.org) API with typed DTOs, configurable caching strategies, and static analysis level max.

> **Requires [PHP 8.4+](https://php.net/releases/) and [Laravel 12+](https://laravel.com/)**

## Features

- **Type-Safe DTOs** — Strict typing with readonly properties and immutability
- **Configurable Cache Strategies** — Multiple caching strategies (remember, revalidate, forever, none)
- **Action Pattern** — Clean separation of concerns with dedicated action classes
- **Full Static Analysis** — PHPStan level max with Larastan
- **Pest Testing** — Comprehensive test coverage with PestPHP
- **Laravel 12 Ready** — Built for modern Laravel applications

## Installation

Install via Composer:

```bash
composer require akira/laravel-packagist
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Akira\Packagist\Providers\PackagistServiceProvider"
```

## Configuration

Edit `config/packagist.php` to customize your setup:

```php
return [
    'use' => [
        'driver' => env('PACKAGIST_CACHE_DRIVER'),
        'strategy' => \Akira\Packagist\Cache\RevalidateCache::class,
        'ttl' => (int) env('PACKAGIST_CACHE_TTL', 3600),
        'tags' => ['packagist'],
        'enabled' => (bool) env('PACKAGIST_CACHE_ENABLED', true),
    ],

    'per_action' => [
        'GetPackageAction' => [
            'strategy' => \Akira\Packagist\Cache\ForeverCache::class,
        ],
        'SearchPackagesAction' => [
            'strategy' => \Akira\Packagist\Cache\RememberCache::class,
            'ttl' => 300,
        ],
    ],
];
```

## Usage

### Get Package Information

```php
use Akira\Packagist\Facades\Packagist;

$package = Packagist::package('laravel/framework');

echo $package->name;                    // 'laravel/framework'
echo $package->description;             // 'The Laravel Framework'
echo $package->downloads['total'];      // total downloads
echo $package->downloads['monthly'];    // monthly downloads
echo $package->downloads['daily'];      // daily downloads
echo $package->favers;                  // total favorites
```

### Search Packages

```php
$results = Packagist::search('laravel', [
    'type' => 'library',
    'tags' => ['framework'],
    'sort' => 'downloads',
]);

foreach ($results['results'] as $package) {
    echo $package['name'];
}
```

### Get Statistics

```php
$stats = Packagist::stats(['period' => 'monthly']);
```

### Get Package Maintainers

```php
$maintainers = Packagist::maintainers('laravel/framework');

foreach ($maintainers as $maintainer) {
    echo $maintainer['name'];
    echo $maintainer['email'];
}
```

## Cache Strategies

### RevalidateCache (Default)
Uses Laravel's cache `remember()` method with optional TTL revalidation.

```php
'strategy' => \Akira\Packagist\Cache\RevalidateCache::class,
```

### RememberCache
Standard cache remember with configurable TTL (default: 3600 seconds).

```php
'strategy' => \Akira\Packagist\Cache\RememberCache::class,
'ttl' => 300, // 5 minutes
```

### ForeverCache
Caches indefinitely until manually cleared.

```php
'strategy' => \Akira\Packagist\Cache\ForeverCache::class,
```

### NoneCache
Disables caching entirely.

```php
'strategy' => \Akira\Packagist\Cache\NoneCache::class,
```

## Custom Cache Implementation

Implement the `CacheContract` to create your own caching strategy:

```php
use Akira\Packagist\Contracts\CacheContract;

class CustomCache implements CacheContract
{
    public function get(string $key, callable $callback, int $ttl = 0, ?string $action = null): mixed
    {
        return $callback();
    }

    public function forget(string $key): void
    {
        // Custom logic
    }
}
```

Register in config:

```php
'strategy' => CustomCache::class,
```

## Custom HTTP Client

Implement the `ClientContract` for custom HTTP handling:

```php
use Akira\Packagist\Contracts\ClientContract;

class CustomClient implements ClientContract
{
    public function get(string $endpoint): mixed
    {
        // Custom implementation
    }

    public function search(string $query, array $filters = []): mixed
    {
        // Custom implementation
    }
}
```

Use it:

```php
$manager = new PackagistManager(config('packagist'));
$manager->withClient(new CustomClient());
```

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test:coverage
```

Type coverage check:

```bash
composer test:type-coverage
```

## Code Quality

Format code with **Pint**:

```bash
composer lint
```

Refactor with **Rector**:

```bash
composer refactor
```

Static analysis with **PHPStan**:

```bash
composer test:types
```

Check refactoring rules:

```bash
composer test:refactor
```

## API Reference

### Packagist Facade

- `package(string $name): PackageDTO` — Get package information
- `search(string $query, array $filters = []): array` — Search packages
- `stats(array $filters = []): array` — Get statistics
- `maintainers(string $package): array` — Get package maintainers
- `withClient(ClientContract $client): PackagistManager` — Use custom HTTP client
- `withCache(CacheContract $cache): PackagistManager` — Use custom cache strategy

### DTOs

#### PackageDTO
- `name: string`
- `description: string`
- `repository: ?string`
- `versions: array<string, VersionDTO>`
- `maintainers: array<MaintainerDTO>`
- `homepage: ?string`
- `license: ?string`
- `downloads: array` — Download statistics with `total`, `monthly`, `daily`
- `favers: int`

#### VersionDTO
- `version: string`
- `name: string`
- `description: string`
- `require: string`
- `keywords: array`
- `homepage: ?string`
- `license: ?string`
- `authors: array`

#### MaintainerDTO
- `name: string`
- `email: string`
- `homepage: ?string`

## License

The MIT License (MIT). See LICENSE file for details.

## Support

For issues, questions, or contributions, visit the [GitHub repository](https://github.com/akira/laravel-packagist).
