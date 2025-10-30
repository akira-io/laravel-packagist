# Cache Strategies Guide

Deep dive into each cache strategy. Understand how they work, when to use them, and real-world examples.

## Overview

Laravel Packagist provides 4 caching strategies, each with different characteristics:

| Strategy | TTL | Expiry | Revalidation | Use Case |
|----------|-----|--------|--------------|----------|
| **Revalidate** | Configurable | Auto | Background | Production APIs |
| **Remember** | Configurable | Auto | None | Short-lived data |
| **Forever** | Indefinite | Manual | None | Static/rarely changing data |
| **None** | N/A | N/A | N/A | Development/debugging |

---

## 1. RevalidateCache (Default)

The most intelligent strategy. Automatically renews cache before expiry.

### How It Works

```
T=0min     : API request → fetch data → save cache
            Cache expires at T=60min

T=55min    : Auto-revalidation job scheduled
            (60min - 5min = 55min)

T=55min+   : Job executes in background
            → API request
            → Update cache
            → Reset expiry timer

T=60min    : Original TTL expires, but cache already renewed 

T=60min+   : Next user request
            → Cache HIT
            → Response: ~5ms
            → Data is fresh
```

### Configuration

```php
use Akira\Packagist\Cache\RevalidateCache;

'use' => [
    'strategy' => RevalidateCache::class,
    'ttl' => 28800,  // 8 hours
],

'auto_revalidation' => [
    'enabled' => true,
    'revalidate_before_expiry' => 300,  // 5 minutes before
    'queue' => 'low',
],
```

### Advantages

- **Zero Latency** - Users never see slow API calls
- **Fresh Data** - Cache always up-to-date when accessed
- **Reduced API Load** - Refresh in background, not on user request
- **Better UX** - Consistent fast response times
- **Production Ready** - Battle-tested pattern

### Disadvantages

- Requires Laravel Queue configured
- Slightly more complex setup
- Background job failures are logged but silent

### When to Use

```
 Production environments
 API with strict SLA requirements
 User-facing endpoints
 Mobile applications (need fast responses)
 High-traffic applications
```

### When NOT to Use

```
 Development/debugging (use NoneCache instead)
 No queue configured (use RememberCache instead)
 ForeverCache appropriate (manually managed expiry)
```

### Real-World Examples

#### Example 1: High-Traffic Dashboard

```php
// Packagist stats on dashboard
'GetStatsAction' => [
    'strategy' => RevalidateCache::class,
    'ttl' => 3600,  // 1 hour
],
```

**Timeline:**
```
00:00 - User A visits dashboard
        → API call: 250ms
        → Cache saved, expires at 01:00
        → Job scheduled at 00:55

00:10 - User B visits dashboard
        → Cache HIT: 5ms 

00:55 - Background job executes
        → API call: 250ms (outside request)
        → Cache renewed, expires at 01:55

01:00 - User C visits dashboard
        → Cache HIT: 5ms  (already renewed!)

01:10 - User D visits dashboard
        → Cache HIT: 5ms 
```

**Result:** Thousands of users all get 5ms response time!

#### Example 2: Package Search Results

```php
'SearchPackagesAction' => [
    'strategy' => RevalidateCache::class,
    'ttl' => 300,  // 5 minutes
],
```

**Search:** "laravel middleware"

```
T=0min  : First search request
          → API call: 150ms
          → Results cached, expires at 5:00
          → Job scheduled at 4:00

T=0:30min: Same search
          → Cache HIT: 3ms 

T=1min  : Same search (different user)
          → Cache HIT: 3ms 

T=4min  : Background job triggers
          → Refreshes results in background

T=5min  : Original TTL expires (but cache fresh)

T=5:30min: Search again
          → Cache HIT: 3ms  (already fresh)
```

---

## 2. RememberCache

Standard TTL-based caching without background renewal.

### How It Works

```
T=0min  : API request → fetch data → save cache
          Cache expires at T=60min

T=30min : Next request
          → Cache HIT: ~5ms
          → Still valid

T=60min : Cache EXPIRES

T=60min+: Next request
          → Cache MISS
          → API call: ~300ms
          → New cache saved
```

### Configuration

```php
use Akira\Packagist\Cache\RememberCache;

'SearchPackagesAction' => [
    'strategy' => RememberCache::class,
    'ttl' => 3600,  // 1 hour
],
```

### Advantages

- Simple, straightforward caching
- Doesn't require queue configured
- Good for non-critical data
- Explicit expiry control
- Lower memory overhead (shorter retention)

### Disadvantages

- Cache misses cause latency spikes
- After expiry, first request pays API cost
- Not ideal for frequently-accessed data

### When to Use

```
 Data where some staleness is acceptable
 Testing (faster, no queue needed)
 Background jobs (latency acceptable)
 Non-critical features
 When queue isn't available
```

### When NOT to Use

```
 User-facing critical APIs
 Zero-latency requirements
 High-traffic endpoints
```

### Real-World Examples

#### Example 1: Admin Dashboard

```php
// Admin stats, not critical
'GetStatsAction' => [
    'strategy' => RememberCache::class,
    'ttl' => 3600,  // 1 hour
],
```

**Rationale:** Admin doesn't need real-time stats, 1 hour is fine.

#### Example 2: Background Job

```php
// In a background job that processes data
$package = Packagist::package('symfony/console');
// Using RememberCache, no worries about latency
// Job will wait 200-300ms, that's acceptable
```

#### Example 3: Cache Warming

Pre-load cache in background job, use RememberCache:

```php
// Command to warm up cache
class WarmPackagistCache extends Command
{
    public function handle()
    {
        $packages = ['laravel/framework', 'symfony/console', 'monolog/monolog'];

        foreach ($packages as $package) {
            // Using RememberCache (no background revalidation)
            Packagist::package($package);
        }

        $this->info('Cache warmed!');
    }
}
```

---

## 3. ForeverCache

Cache never expires automatically. Persists until manual cleanup.

### How It Works

```
T=0min       : API request → fetch data → save cache
               NO EXPIRY SET

T=1000 days  : Cache still valid!
               → Cache HIT: ~5ms
               → Same data as T=0min

Manual Flush : cache()->tags('packagist')->flush()
               → Cache removed
               → Next request: API call needed
```

### Configuration

```php
use Akira\Packagist\Cache\ForeverCache;

'GetPackageAction' => [
    'strategy' => ForeverCache::class,
    // No TTL specified - cache never expires
],
```

### Advantages

- Perfect for static/rarely-changing data
- Best performance (cache always hit)
- Control when to refresh via flush
- Simplest to understand

### Disadvantages

- Data can become stale if not refreshed
- Requires manual cache management
- Risk of serving outdated information
- Need explicit cleanup strategy

### When to Use

```
 Package details (rarely change)
 Maintainer information (rarely changes)
 Long-lived reference data
 Data refreshed via scheduled commands
 API documentation/metadata
```

### When NOT to Use

```
 Frequently-changing data
 Time-sensitive information
 Without a refresh strategy
 When auto-expiry is needed
```

### Cleanup Strategies

#### Strategy 1: Tag-Based Flush

```php
// Clear all Packagist caches
cache()->tags('packagist')->flush();

// Use in a command
php artisan packagist:cache:refresh
```

#### Strategy 2: Selective Flush

```php
// Clear specific package
cache()->forget('packagist:package:laravel/framework');

// Clear all package caches (but not searches)
cache()->forget('packagist:package:*');  // Note: Pattern may not work with all drivers
```

#### Strategy 3: Schedule-Based Refresh

```php
// In AppServiceProvider boot()
protected function schedule(Schedule $schedule)
{
    // Refresh cache daily at 2 AM
    $schedule->command('packagist:cache:refresh')->dailyAt('02:00');
}
```

#### Strategy 4: Event-Based Refresh

```php
// Create a webhook listener for Packagist events
Route::post('/webhooks/packagist', function (Request $request) {
    // New package published, refresh cache
    cache()->tags('packagist')->flush();

    return response()->json(['status' => 'ok']);
});
```

### Real-World Examples

#### Example 1: Package Details Page

```php
// In controller
public function show($vendor, $package)
{
    $pkg = Packagist::package("{$vendor}/{$package}");

    // Using ForeverCache - data rarely changes
    // Manual refresh via command when needed

    return view('package.show', ['package' => $pkg]);
}

// Refresh command
class RefreshPackageCache extends Command
{
    protected $signature = 'packagist:refresh {package}';

    public function handle()
    {
        $package = $this->argument('package');
        cache()->forget("packagist:package:{$package}");

        Packagist::package($package);  // Re-fetch and cache
        $this->info("Refreshed {$package}");
    }
}
```

#### Example 2: Bulk Data Import

```php
class ImportPopularPackages extends Command
{
    public function handle()
    {
        $popular = ['laravel/framework', 'symfony/console', ...];

        foreach ($popular as $package) {
            // Cache indefinitely with ForeverCache
            Packagist::package($package);
        }

        $this->info('Popular packages cached indefinitely');
    }
}

// Later, refresh all
public function refreshCommand()
{
    cache()->tags('packagist')->flush();
    // This command will re-fetch everything
}
```

---

## 4. NoneCache

No caching at all. Always fetch fresh from API.

### How It Works

```
T=0min  : API request → fetch data → return (NO CACHE)

T=1min  : Same request
          → API call again: ~300ms
          → Return fresh data (NO CACHE)

T=5min  : Same request again
          → API call again: ~300ms
          → Return fresh data (NO CACHE)
```

### Configuration

```php
use Akira\Packagist\Cache\NoneCache;

'use' => [
    'strategy' => NoneCache::class,
],
```

### Advantages

- Always fresh data
- No cache invalidation issues
- Perfect for development
- Easiest to understand
- No memory overhead

### Disadvantages

- High API load
- Slow responses (~300ms every time)
- Wastes bandwidth
- Not production-ready
- Strain on Packagist API

### When to Use

```
 Development mode
 Debugging API issues
 Testing API responses
 CI/CD environments
 One-off scripts
```

### When NOT to Use

```
 Production (ever!)
 User-facing applications
 High-traffic scenarios
```

### Example: Development Configuration

```php
// config/packagist.php
return [
    'use' => [
        'strategy' => match(env('APP_ENV')) {
            'production' => RevalidateCache::class,
            'testing' => RememberCache::class,
            'local' => NoneCache::class,  // Development
        },
    ],
];

// Or use environment variable
'use' => [
    'strategy' => env('PACKAGIST_CACHE_STRATEGY', NoneCache::class),
],

// .env
PACKAGIST_CACHE_STRATEGY=NoneCache::class  # Development
PACKAGIST_CACHE_STRATEGY=RevalidateCache::class  # Production
```

---

## Strategy Comparison Table

```
┌─────────────────┬────────────┬──────────┬────────────────┬──────────┐
│ Strategy        │ TTL        │ Expiry   │ Background Job │ Latency  │
├─────────────────┼────────────┼──────────┼────────────────┼──────────┤
│ Revalidate      │ 8h default │ Auto+Job │ Yes            │ 5ms      │
│ Remember        │ 1h default │ Auto     │ No             │ 5ms/300ms│
│ Forever         │ Infinite   │ Manual   │ No             │ 5ms      │
│ None            │ N/A        │ N/A      │ No             │ 300ms    │
└─────────────────┴────────────┴──────────┴────────────────┴──────────┘
```

---

## Choosing the Right Strategy

Use this decision tree:

```
Question 1: How fresh must data be?
├─ Always fresh → NoneCache (dev only!)
├─ Within 5 minutes → Revalidate or Remember (5 min TTL)
├─ Within 1 hour → Revalidate or Remember (1 hour TTL)
└─ Can be stale → Forever or Remember (long TTL)

Question 2: Is this production?
├─ NO (development) → NoneCache
└─ YES → Continue

Question 3: Is queue available?
├─ YES → Use Revalidate (best choice!)
└─ NO → Use Remember or Forever

Question 4: How often does data change?
├─ Every minute → Revalidate (TTL: 300s)
├─ Every hour → Revalidate (TTL: 3600s)
├─ Every day → Remember or Revalidate (TTL: 86400s)
└─ Never → Forever
```

---

## Per-Action Strategy Example

Combining strategies for optimal performance:

```php
'per_action' => [
    // Rarely changes → Forever
    'GetPackageAction' => [
        'strategy' => ForeverCache::class,
    ],

    // Changes frequently → Revalidate with short TTL
    'SearchPackagesAction' => [
        'strategy' => RevalidateCache::class,
        'ttl' => 300,
    ],

    // Moderate change → Remember
    'GetStatsAction' => [
        'strategy' => RememberCache::class,
        'ttl' => 3600,
    ],

    // Always fresh needed → None
    'GetMaintainersAction' => [
        'strategy' => NoneCache::class,
    ],
],
```

---

## Performance Comparison

Real-world performance metrics:

```
Scenario: 1000 requests/day, TTL=1h, 24 possible unique queries

With NoneCache:
- 1000 API calls/day
- 1000 × 300ms = 300 seconds total latency
- Average: 300ms per request
- Packagist load: HIGH

With RememberCache:
- 24 cache misses (one per hour) + 976 hits
- 24 × 300ms = 7.2 seconds latency
- 976 × 5ms = 4.88 seconds latency
- Total: 12 seconds across 1000 requests
- Average: 12ms per request 
- Packagist load: MEDIUM

With RevalidateCache:
- 0 cache misses (background renewal)
- 1000 × 5ms = 5 seconds latency
- Average: 5ms per request 
- Packagist load: LOW 
```

---

## Next Steps

- **[API Usage Guide](./05-api-usage.md)** - Learn the API
- **[Auto-Revalidation](./06-auto-revalidation.md)** - Deep dive into background renewal
- **[Testing Guide](./07-testing.md)** - Test your implementation

---

**Tip:** Start with `RevalidateCache` for production. It's the goldilocks strategy - not too simple, not too complex, just right!
