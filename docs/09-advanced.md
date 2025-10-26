# Advanced Topics Guide

Advanced features and customization options for Laravel Packagist.

## Custom Cache Implementations

Create your own cache strategy by implementing the `CacheContract` interface.

### Create Custom Strategy

```php
<?php

declare(strict_types=1);

namespace App\Cache;

use Akira\Packagist\Contracts\CacheContract;

class CustomCache implements CacheContract
{
    /**
     * Get value from cache or execute callback
     *
     * @param  string  $key
     * @param  callable  $callback
     * @param  int  $ttl
     * @param  string|null  $action
     * @return mixed
     */
    public function get(
        string $key,
        callable $callback,
        int $ttl = 0,
        ?string $action = null,
    ): mixed {
        // Your custom caching logic here
        $result = cache()->get($key);

        if ($result !== null) {
            return $result;  // Cache hit
        }

        // Cache miss - execute callback
        $result = $callback();

        // Save to cache
        cache()->put($key, $result, $ttl);

        return $result;
    }

    /**
     * Forget/remove from cache
     *
     * @param  string  $key
     * @return void
     */
    public function forget(string $key): void
    {
        cache()->forget($key);
    }
}
```

### Register Custom Strategy

```php
// In config/packagist.php
use App\Cache\CustomCache;

'use' => [
    'strategy' => CustomCache::class,
],

// Or override per-action
'per_action' => [
    'GetPackageAction' => [
        'strategy' => CustomCache::class,
    ],
],
```

### Example: Cache with Compression

```php
<?php

declare(strict_types=1);

namespace App\Cache;

use Akira\Packagist\Contracts\CacheContract;

class CompressedCache implements CacheContract
{
    public function get(
        string $key,
        callable $callback,
        int $ttl = 0,
        ?string $action = null,
    ): mixed {
        // Try to get compressed value
        $compressed = cache()->get($key . ':compressed');

        if ($compressed !== null) {
            // Decompress
            return gzuncompress($compressed);
        }

        // Execute callback
        $result = $callback();

        // Compress and store
        $compressed = gzcompress(serialize($result), 9);
        cache()->put($key . ':compressed', $compressed, $ttl);

        return $result;
    }

    public function forget(string $key): void
    {
        cache()->forget($key . ':compressed');
    }
}
```

### Example: Cache with Encryption

```php
<?php

declare(strict_types=1);

namespace App\Cache;

use Akira\Packagist\Contracts\CacheContract;
use Illuminate\Support\Facades\Crypt;

class EncryptedCache implements CacheContract
{
    public function get(
        string $key,
        callable $callback,
        int $ttl = 0,
        ?string $action = null,
    ): mixed {
        $encrypted = cache()->get($key . ':encrypted');

        if ($encrypted !== null) {
            // Decrypt
            return Crypt::decrypt($encrypted);
        }

        // Execute callback
        $result = $callback();

        // Encrypt and store
        $encrypted = Crypt::encrypt($result);
        cache()->put($key . ':encrypted', $encrypted, $ttl);

        return $result;
    }

    public function forget(string $key): void
    {
        cache()->forget($key . ':encrypted');
    }
}
```

---

## Custom Validators

Create custom validators by extending validation logic.

### Create Custom Validator

```php
<?php

declare(strict_types=1);

namespace App\Validators;

class PackageRepositoryValidator
{
    /**
     * Validate that package exists on GitHub
     */
    public static function validate(string $package): bool
    {
        // Extract vendor/package
        [$vendor, $name] = explode('/', $package);

        // Check if GitHub repo exists
        $response = Http::head("https://api.github.com/repos/{$vendor}/{$name}");

        return $response->status() === 200;
    }

    /**
     * Validate or throw exception
     */
    public static function validateOrFail(string $package): void
    {
        if (!static::validate($package)) {
            throw new \InvalidArgumentException(
                "Package {$package} not found on GitHub"
            );
        }
    }
}
```

### Use Custom Validator

```php
// In action or controller
PackageRepositoryValidator::validateOrFail('laravel/framework');

$package = Packagist::package('laravel/framework');
```

### Example: Business Logic Validator

```php
<?php

declare(strict_types=1);

namespace App\Validators;

class PackageSecurityValidator
{
    /**
     * Validate package security (no vulnerabilities)
     */
    public static function validate(string $package): bool
    {
        // Check for known vulnerabilities
        $vulnerabilities = $this->checkVulnerabilities($package);

        return count($vulnerabilities) === 0;
    }

    private static function checkVulnerabilities(string $package): array
    {
        // Example: Call vulnerability database API
        $response = Http::get('https://vulnerabilities.example.com/check', [
            'package' => $package,
        ]);

        return $response->json('vulnerabilities', []);
    }

    public static function validateOrFail(string $package): void
    {
        if (!static::validate($package)) {
            throw new \Exception(
                "Package {$package} has known vulnerabilities"
            );
        }
    }
}
```

---

## Custom Actions

Create custom actions for additional functionality.

### Create Custom Action

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Contracts\CacheContract;

class GetPopularPackagesAction
{
    public function __construct(
        private readonly ClientContract $client,
        private readonly CacheContract $cache,
    ) {}

    /**
     * Get most popular packages
     *
     * @param  int  $limit
     * @return array<int, array<string, mixed>>
     */
    public function handle(int $limit = 10): array
    {
        return $this->cache->get(
            'packagist:popular-packages:' . $limit,
            fn() => $this->fetchPopular($limit),
            86400,  // Cache for 1 day
            'GetPopularPackagesAction'
        );
    }

    private function fetchPopular(int $limit): array
    {
        // Fetch all packages and sort by downloads
        $response = $this->client->get('packages.json');

        $packages = collect($response['packages'] ?? [])
            ->sortByDesc('downloads')
            ->take($limit)
            ->values()
            ->toArray();

        return $packages;
    }
}
```

### Register Custom Action

```php
// In service provider
use App\Actions\GetPopularPackagesAction;

$this->app->singleton(GetPopularPackagesAction::class, function () {
    return new GetPopularPackagesAction(
        $this->app->make(ClientContract::class),
        $this->app->make(CacheContract::class),
    );
});

// Or use directly
$action = new GetPopularPackagesAction($client, $cache);
$popular = $action->handle(20);
```

### Example: Trending Packages Action

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Contracts\CacheContract;

class GetTrendingPackagesAction
{
    public function __construct(
        private readonly ClientContract $client,
        private readonly CacheContract $cache,
    ) {}

    /**
     * Get trending packages (most downloads in last period)
     */
    public function handle(string $period = 'month', int $limit = 10): array
    {
        return $this->cache->get(
            "packagist:trending:{$period}:{$limit}",
            fn() => $this->fetchTrending($period, $limit),
            3600,  // Cache for 1 hour
            'GetTrendingPackagesAction'
        );
    }

    private function fetchTrending(string $period, int $limit): array
    {
        // Fetch stats API to get trending
        $response = $this->client->get('stats');

        // Transform based on period
        $trending = match($period) {
            'day' => $this->getTrendingDay($response),
            'month' => $this->getTrendingMonth($response),
            'year' => $this->getTrendingYear($response),
            default => [],
        };

        return collect($trending)
            ->take($limit)
            ->values()
            ->toArray();
    }

    private function getTrendingDay(array $stats): array
    {
        // Logic to extract daily trends
        return $stats['daily_downloads'] ?? [];
    }

    private function getTrendingMonth(array $stats): array
    {
        return $stats['monthly_downloads'] ?? [];
    }

    private function getTrendingYear(array $stats): array
    {
        return $stats['yearly_downloads'] ?? [];
    }
}
```

---

## Extended Facade

Create a custom facade with additional methods.

### Create Extended Facade

```php
<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use Akira\Packagist\PackagistManager;
use App\Actions\GetPopularPackagesAction;
use App\Actions\GetTrendingPackagesAction;

/**
 * @method static \Akira\Packagist\DTOs\PackageDTO package(string $package)
 * @method static array search(string $query, array $filters = [])
 * @method static array stats(array $filters = [])
 * @method static array maintainers(string $package)
 * @method static array popular(int $limit = 10)
 * @method static array trending(string $period = 'month', int $limit = 10)
 */
class ExtendedPackagist extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'extended-packagist';
    }
}
```

### Register Extended Facade

```php
// In service provider
$this->app->singleton('extended-packagist', function () {
    $manager = $this->app->make(PackagistManager::class);

    return new class($manager) {
        public function __construct(
            private readonly PackagistManager $manager,
        ) {}

        // Delegate original methods
        public function package(string $package)
        {
            return $this->manager->package($package);
        }

        public function search(string $query, array $filters = [])
        {
            return $this->manager->search($query, $filters);
        }

        // Custom methods
        public function popular(int $limit = 10): array
        {
            $action = new GetPopularPackagesAction(...);
            return $action->handle($limit);
        }

        public function trending(string $period = 'month', int $limit = 10): array
        {
            $action = new GetTrendingPackagesAction(...);
            return $action->handle($period, $limit);
        }
    };
});

// In alias
'ExtendedPackagist' => App\Facades\ExtendedPackagist::class,
```

### Use Extended Facade

```php
// Use new custom methods
$popular = ExtendedPackagist::popular(20);
$trending = ExtendedPackagist::trending('month', 15);

// Still use original methods
$package = ExtendedPackagist::package('laravel/framework');
```

---

## Custom DTOs

Create specialized DTOs for your domain.

### Create Custom DTO

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Popular package information
 */
readonly class PopularPackageDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public int $downloads,
        public int $favers,
        public float $downloadsPerDay,
    ) {}

    /**
     * Create from PackageDTO
     */
    public static function fromPackage(
        \Akira\Packagist\DTOs\PackageDTO $package
    ): self {
        // Calculate downloads per day (rough estimate)
        $daysOnline = 365 * 5;  // Assume 5 years
        $downloadsPerDay = $package->downloads / $daysOnline;

        return new self(
            name: $package->name,
            description: $package->description,
            downloads: $package->downloads,
            favers: $package->favers,
            downloadsPerDay: $downloadsPerDay,
        );
    }

    /**
     * Check if package is "viral"
     */
    public function isViral(): bool
    {
        return $this->downloadsPerDay > 100_000;
    }

    /**
     * Get popularity score (0-100)
     */
    public function getPopularityScore(): float
    {
        $maxDownloads = 1_000_000_000;  // 1 billion

        return min(
            100,
            ($this->downloads / $maxDownloads) * 100
        );
    }
}
```

### Use Custom DTO

```php
// In action or service
$package = Packagist::package('laravel/framework');
$popular = PopularPackageDTO::fromPackage($package);

echo $popular->isViral();  // true/false
echo $popular->getPopularityScore();  // 0-100
```

---

## Performance Optimization

### 1. Request Batching

```php
// Bad: N+1 problem
foreach ($packageNames as $package) {
    Packagist::package($package);  // Multiple API calls
}

// Good: Batch if API supports
class BatchPackagistAction
{
    public function handle(array $packages): array
    {
        // Fetch all, then get details for uncached ones
        $results = [];

        foreach ($packages as $package) {
            $results[] = Packagist::package($package);
        }

        return $results;
    }
}
```

### 2. Lazy Loading

```php
// Lazy load related data
class LazyPackageDTO
{
    private ?array $versions = null;
    private ?array $maintainers = null;

    public function getVersions(): array
    {
        if ($this->versions === null) {
            $this->versions = $this->fetchVersions();
        }

        return $this->versions;
    }

    public function getMaintainers(): array
    {
        if ($this->maintainers === null) {
            $this->maintainers = $this->fetchMaintainers();
        }

        return $this->maintainers;
    }
}
```

### 3. Caching Strategies per Data Type

```php
'per_action' => [
    // Frequently accessed, stable
    'GetPackageAction' => [
        'strategy' => ForeverCache::class,
        'ttl' => null,
    ],

    // Changes frequently
    'SearchPackagesAction' => [
        'strategy' => RevalidateCache::class,
        'ttl' => 300,  // Short TTL
    ],

    // Rarely accessed
    'GetStatsAction' => [
        'strategy' => RememberCache::class,
        'ttl' => 86400,  // Long TTL
    ],
],
```

### 4. Query Optimization

```php
// Use filters to reduce response size
$results = Packagist::search('laravel', [
    'type' => 'library',  // Filter to libraries only
]);

// Only fetch what you need
foreach ($results as $result) {
    $name = $result['name'];
    // Don't fetch full details unless needed
    if ($user->wants_details) {
        $full = Packagist::package($name);
    }
}
```

---

## Monitoring and Observability

### 1. Event-Based Monitoring

```php
// Create events for monitoring
Event::listen('packagist.api.call', function ($data) {
    Log::channel('performance')->info('API Call', [
        'endpoint' => $data['endpoint'],
        'duration' => $data['duration'],
        'cache_hit' => $data['cache_hit'],
    ]);
});

// Dispatch in action
Event::dispatch('packagist.api.call', [
    'endpoint' => $endpoint,
    'duration' => $duration,
    'cache_hit' => $fromCache,
]);
```

### 2. Metrics Collection

```php
// Track metrics
class PackagistMetrics
{
    public static function recordApiCall(
        string $endpoint,
        float $duration,
        bool $cached
    ): void {
        Metrics::record('packagist.api.calls', 1, [
            'endpoint' => $endpoint,
            'cached' => $cached ? 'yes' : 'no',
        ]);

        Metrics::record('packagist.api.duration', $duration, [
            'endpoint' => $endpoint,
        ]);
    }

    public static function recordCacheHit(string $key): void
    {
        Metrics::increment('packagist.cache.hits');
    }

    public static function recordCacheMiss(string $key): void
    {
        Metrics::increment('packagist.cache.misses');
    }
}
```

### 3. Health Checks

```php
// Route for health checks
Route::get('/health/packagist', function () {
    $health = [
        'cache' => $this->checkCache(),
        'queue' => $this->checkQueue(),
        'api' => $this->checkApi(),
    ];

    return response()->json($health, 200);
});

private function checkCache(): bool
{
    try {
        Cache::put('health-check', true, 1);
        return Cache::get('health-check') === true;
    } catch (Exception) {
        return false;
    }
}

private function checkQueue(): bool
{
    try {
        return Queue::size() >= 0;  // Can access queue
    } catch (Exception) {
        return false;
    }
}

private function checkApi(): bool
{
    try {
        $response = Http::get('https://repo.packagist.org/packages.json');
        return $response->status() === 200;
    } catch (Exception) {
        return false;
    }
}
```

---

## Security Considerations

### 1. Validate User Input

```php
// Always validate package names from user input
public function viewPackage(Request $request)
{
    $packageName = $request->input('package');

    // Validate format
    if (!PackageValidator::validate($packageName)) {
        throw ValidationException::withMessages([
            'package' => 'Invalid package name format',
        ]);
    }

    $package = Packagist::package($packageName);

    return view('package.show', compact('package'));
}
```

### 2. Rate Limiting

```php
// Limit API calls per user
Route::get('/package/{package}', function ($package) {
    RateLimiter::attempt(
        'packagist:' . auth()->id(),
        $perMinute = 30,
        function () use ($package) {
            return Packagist::package($package);
        },
    );
})->middleware('throttle:30,1');  // 30 requests per minute
```

### 3. Cache Invalidation Security

```php
// Only allow authenticated users to clear cache
Route::post('/admin/cache/clear', function () {
    if (!auth()->user()->isAdmin()) {
        abort(403);
    }

    cache()->tags('packagist')->flush();

    return response()->json(['message' => 'Cache cleared']);
})->middleware(['auth', 'admin']);
```

---

## Testing Advanced Features

### Test Custom Cache

```php
test('custom cache works correctly', function () {
    $cache = new CustomCache();

    // First call executes callback
    $result = $cache->get('key', function () {
        return 'value';
    }, 3600);

    expect($result)->toBe('value');

    // Second call uses cache
    $cached = $cache->get('key', function () {
        return 'different';
    }, 3600);

    expect($cached)->toBe('value');
});
```

---

## Next Steps

- Back to [API Usage](./05-api-usage.md)
- See [Configuration](./03-configuration.md) for more options
- Check [Testing Guide](./07-testing.md) for testing advanced features

---

**Advanced features unlock custom workflows tailored to your application!**
