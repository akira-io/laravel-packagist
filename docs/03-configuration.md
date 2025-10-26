# Configuration Guide

Complete reference for all Laravel Packagist configuration options. Learn how to customize the package for your specific needs.

## Configuration File Location

The main configuration file is located at `config/packagist.php`:

```
laravel-project/
├── config/
│   └── packagist.php          # Main configuration
├── .env                        # Environment overrides
└── ...
```

## Configuration Structure

The configuration is organized into three main sections:

```php
return [
    'use' => [
        // Global cache configuration
    ],

    'auto_revalidation' => [
        // Auto-renewal settings
    ],

    'per_action' => [
        // Action-specific overrides
    ],
];
```

## Section 1: Global Cache Configuration (`use`)

This section defines default caching behavior for all API calls.

### `driver`

Specifies which cache driver to use.

**Type:** `string|null`
**Default:** `null` (uses Laravel's default)
**Options:** `'redis'`, `'file'`, `'memcached'`, `'database'`, `null`

**Example:**

```php
'driver' => env('PACKAGIST_CACHE_DRIVER', null),

// In .env:
PACKAGIST_CACHE_DRIVER=redis
```

**Explanation:**
- `null` - Uses your Laravel app's configured cache driver
- `'redis'` - Fast in-memory cache (recommended for production)
- `'file'` - File-based cache (default in development)
- `'memcached'` - High-performance distributed cache
- `'database'` - Database-backed cache (slowest option)

**Best Practice:**
```env
# Development
PACKAGIST_CACHE_DRIVER=file

# Production (fast & distributed)
PACKAGIST_CACHE_DRIVER=redis
```

### `strategy`

Defines which caching strategy to use globally.

**Type:** `string` (class name)
**Default:** `RevalidateCache::class`
**Options:** `RevalidateCache`, `RememberCache`, `ForeverCache`, `NoneCache`

**Example:**

```php
use Akira\Packagist\Cache\RevalidateCache;

'strategy' => env('PACKAGIST_CACHE_STRATEGY', RevalidateCache::class),
```

**Strategy Comparison:**

| Strategy | TTL | Behavior | Use Case |
|----------|-----|----------|----------|
| `RevalidateCache` | 8h (default) | Auto-renews before expiry | Most APIs |
| `RememberCache` | Configurable | Standard remember with TTL | Short-lived data |
| `ForeverCache` | Indefinite | Never expires automatically | Static data |
| `NoneCache` | N/A | Never caches | Debug/testing |

**Details:**
- **RevalidateCache**: Perfect for production. Automatically refreshes cache in background 5 minutes before expiry.
- **RememberCache**: Simple TTL-based cache. Expires completely on TTL.
- **ForeverCache**: Cache persists indefinitely. Must be manually cleared.
- **NoneCache**: Always fetches fresh from API. No caching at all.

**Recommendation:** Keep `RevalidateCache::class` for production.

### `ttl`

Time to Live - how long (in seconds) to keep data in cache.

**Type:** `int`
**Default:** `28800` (8 hours)
**Range:** `0` to any positive integer

**Example:**

```php
'ttl' => (int) env('PACKAGIST_CACHE_TTL', 28800),

// In .env:
PACKAGIST_CACHE_TTL=3600
```

**Common Values:**

```php
300         // 5 minutes - very fresh data
3600        // 1 hour - moderate TTL
28800       // 8 hours - default (stable data)
86400       // 1 day - very stable data
604800      // 1 week - rarely changing data
```

**Decision Tree:**

```
Is data changing every minute?
  → Yes: TTL = 300 (5 minutes)
  → No ↓

Is data changing hourly?
  → Yes: TTL = 3600 (1 hour)
  → No ↓

Is data changing daily?
  → Yes: TTL = 28800 (8 hours)
  → No ↓

Almost never changes?
  → Yes: TTL = 86400+ (1 day+)
```

**Per-Action TTL:**
Override for specific actions:

```php
'per_action' => [
    'SearchPackagesAction' => [
        'ttl' => 300,  // Short-lived search results
    ],
    'GetPackageAction' => [
        'ttl' => 86400, // Package details rarely change
    ],
]
```

### `tags`

Cache tags to group related cache entries together.

**Type:** `array<string>`
**Default:** `['packagist']`

**Example:**

```php
'tags' => ['packagist', 'api'],
```

**Use Cases:**

Invalidate all Packagist caches at once:

```php
// In a command or controller
cache()->tags('packagist')->flush();
```

Invalidate multiple cache groups:

```php
cache()->tags(['packagist', 'api'])->flush();
```

### `enabled`

Enable or disable caching globally.

**Type:** `bool`
**Default:** `true`

**Example:**

```php
'enabled' => (bool) env('PACKAGIST_CACHE_ENABLED', true),

// In .env:
PACKAGIST_CACHE_ENABLED=false
```

**Use Cases:**
- **Development**: Disable to always fetch fresh data
- **Debugging**: Disable to verify API responses
- **Testing**: Disable to ensure fresh data in tests

**Disable in .env:**

```env
# Development - always fetch fresh
PACKAGIST_CACHE_ENABLED=false

# Production - enable caching
PACKAGIST_CACHE_ENABLED=true
```

---

## Section 2: Auto-Revalidation Configuration

Automatic background cache refresh before expiry.

### `enabled`

Enable or disable automatic cache revalidation.

**Type:** `bool`
**Default:** `true`

**Example:**

```php
'enabled' => (bool) env('PACKAGIST_AUTO_REVALIDATION', true),
```

**How It Works:**

```
Without Auto-Revalidation:
T=0min : Cache saved (TTL=3600s)
T=60min: Cache EXPIRES
T=60m+ : User request → slow API call → latency 

With Auto-Revalidation:
T=0min : Cache saved (TTL=3600s)
T=55min: Job scheduled in background
T=55m+ : Job executes → API call → cache renewed
T=60min: Cache already fresh → fast response 
```

**When to Enable:**
- Production APIs with strict SLA
- User-facing requests
- Zero-latency requirements
- Queue is available

**When to Disable:**
- Development/debugging
- No queue configured
- ForeverCache strategy (doesn't expire)
- NoneCache strategy (no caching)

### `revalidate_before_expiry`

Seconds before TTL expires to trigger cache renewal.

**Type:** `int`
**Default:** `300` (5 minutes)

**Example:**

```php
'revalidate_before_expiry' => (int) env('PACKAGIST_REVALIDATE_BEFORE_EXPIRY', 300),
```

**Formula:**

```
Job Execution Time = TTL - revalidate_before_expiry

Example:
TTL = 3600 (1 hour)
revalidate_before_expiry = 300 (5 minutes)
Job executes at: 3600 - 300 = 3300 seconds (55 minutes)
```

**Recommended Values:**

```php
// For short TTL (5 minutes)
'ttl' => 300,
'revalidate_before_expiry' => 60,     // Renew 1 min before

// For moderate TTL (1 hour)
'ttl' => 3600,
'revalidate_before_expiry' => 300,    // Renew 5 min before

// For long TTL (1 day)
'ttl' => 86400,
'revalidate_before_expiry' => 600,    // Renew 10 min before
```

**Important:** `revalidate_before_expiry` must be less than `ttl`!

```php
//  WRONG - revalidate_before_expiry > ttl
'ttl' => 300,
'revalidate_before_expiry' => 600,

//  CORRECT - revalidate_before_expiry < ttl
'ttl' => 3600,
'revalidate_before_expiry' => 300,
```

### `queue`

Queue name for auto-revalidation jobs.

**Type:** `string`
**Default:** `'default'`
**Options:** Any queue configured in `config/queue.php`

**Example:**

```php
'queue' => env('PACKAGIST_QUEUE', 'default'),

// In .env:
PACKAGIST_QUEUE=low
```

**Available Queues:**

```php
// config/queue.php
'connections' => [
    'redis' => [...],
]

// Queue names you can use:
'default'  // Default queue
'low'      // Low priority
'high'     // High priority
'custom'   // Custom queue
```

**Why Use Priority Queues?**

```
Without priority:
[Job 1] [Job 2] [Auto-Reval Job] [Job 4] [Job 5]
                     ↑
              Delayed by other jobs!

With low priority queue:
[Job 1] [Job 2] [Job 4] [Job 5]    [Auto-Reval Job]
[High Priority Queue] [Low Priority Queue]
```

**Recommendation:** Use `'low'` queue for auto-revalidation:

```php
'queue' => env('PACKAGIST_QUEUE', 'low'),
```

---

## Section 3: Per-Action Configuration

Override global settings for specific API actions.

### Structure

```php
'per_action' => [
    'ActionName' => [
        'strategy' => CacheStrategyClass::class,
        'ttl' => 300,
    ],
]
```

### Available Actions

1. `GetPackageAction` - Fetch single package details
2. `SearchPackagesAction` - Search for packages
3. `GetStatsAction` - Get Packagist statistics
4. `GetMaintainersAction` - Get package maintainers

### GetPackageAction

Retrieves detailed information about a specific package.

**Characteristics:**
- Data changes rarely (only on new release)
- Once loaded, stable for long periods
- High computation cost to fetch

**Recommended Configuration:**

```php
'GetPackageAction' => [
    'strategy' => ForeverCache::class,
    // No TTL - data persists until manual cleanup
],
```

**Why ForeverCache?**
```
Package Details:
- laravel/framework
  - Name: rarely changes
  - Description: rarely changes
  - Versions: add over time (but rarely removed)
  - Maintainers: rarely change

Better to cache forever and manually refresh when needed.
```

**Manual Refresh:**

```php
// Clear all package caches
cache()->tags('packagist')->flush();

// Clear specific package
cache()->forget('packagist:package:laravel/framework');
```

### SearchPackagesAction

Searches for packages matching criteria.

**Characteristics:**
- Results change as new packages are added
- Users expect relatively fresh results
- Less expensive than fetching individual packages

**Recommended Configuration:**

```php
'SearchPackagesAction' => [
    'strategy' => RememberCache::class,
    'ttl' => 300, // 5 minutes
],
```

**With Auto-Revalidation:**
```
TTL = 300 (5 minutes)
revalidate_before_expiry = 60 (1 minute)
Effect: Cache renewed every ~4 minutes 55 seconds
```

**Example:**

```php
// Search results cached for 5 minutes
// Renewed in background after 4 minutes
$results = Packagist::search('laravel');

// Each search has unique cache key
$results = Packagist::search('symfony'); // Different cache
```

### GetStatsAction

Retrieves Packagist statistics.

**Characteristics:**
- Very stable data (updated daily/hourly)
- Not critical for real-time accuracy
- Low computation cost

**Recommended Configuration:**

```php
'GetStatsAction' => [
    'strategy' => RememberCache::class,
    'ttl' => 3600, // 1 hour
],
```

**Usage:**

```php
$stats = Packagist::stats();
// Cached for 1 hour
// Users get consistent stats for 1 hour period
```

### GetMaintainersAction

Retrieves maintainers for a specific package.

**Characteristics:**
- Changes rarely (maintainers don't change often)
- Derived from package data
- Similar TTL to package details

**Recommended Configuration:**

```php
'GetMaintainersAction' => [
    'strategy' => ForeverCache::class,
    'ttl' => 86400, // Optional fallback
],
```

---

## Complete Configuration Example

Here's a complete, production-ready configuration:

```php
<?php

declare(strict_types=1);

use Akira\Packagist\Cache\ForeverCache;
use Akira\Packagist\Cache\RememberCache;
use Akira\Packagist\Cache\RevalidateCache;

return [
    // Global cache settings
    'use' => [
        'driver' => env('PACKAGIST_CACHE_DRIVER', 'redis'),
        'strategy' => RevalidateCache::class,
        'ttl' => (int) env('PACKAGIST_CACHE_TTL', 28800),
        'tags' => ['packagist'],
        'enabled' => (bool) env('PACKAGIST_CACHE_ENABLED', true),
    ],

    // Automatic background cache refresh
    'auto_revalidation' => [
        'enabled' => (bool) env('PACKAGIST_AUTO_REVALIDATION', true),
        'revalidate_before_expiry' => (int) env('PACKAGIST_REVALIDATE_BEFORE_EXPIRY', 300),
        'queue' => env('PACKAGIST_QUEUE', 'low'),
    ],

    // Per-action overrides
    'per_action' => [
        'GetPackageAction' => [
            'strategy' => ForeverCache::class,
        ],
        'SearchPackagesAction' => [
            'strategy' => RememberCache::class,
            'ttl' => 300,
        ],
        'GetStatsAction' => [
            'strategy' => RememberCache::class,
            'ttl' => 3600,
        ],
    ],
];
```

### Corresponding .env File

```env
# Cache Configuration
PACKAGIST_CACHE_DRIVER=redis
PACKAGIST_CACHE_TTL=28800
PACKAGIST_CACHE_ENABLED=true

# Auto-Revalidation
PACKAGIST_AUTO_REVALIDATION=true
PACKAGIST_REVALIDATE_BEFORE_EXPIRY=300
PACKAGIST_QUEUE=low

# Laravel Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Laravel Queue
QUEUE_CONNECTION=redis
```

---

## Configuration Decision Tree

Use this to choose the right settings:

```
Question 1: Are you in development?
├─ YES → PACKAGIST_CACHE_ENABLED=false (always fetch fresh)
└─ NO → Continue

Question 2: Do you have a queue configured?
├─ NO → PACKAGIST_AUTO_REVALIDATION=false
└─ YES → Continue (keep auto-revalidation enabled)

Question 3: Do you have Redis available?
├─ YES → PACKAGIST_CACHE_DRIVER=redis
└─ NO → PACKAGIST_CACHE_DRIVER=file

Question 4: Is response time critical?
├─ YES → Keep RevalidateCache strategy, short revalidate_before_expiry (60s)
└─ NO → Can use RememberCache with longer TTL

Question 5: How often does data change?
├─ Hourly → TTL=3600
├─ Daily → TTL=28800
├─ Never → Use ForeverCache
└─ Every minute → TTL=300
```

---

## Next Steps

- **[Cache Strategies Deep Dive](./04-cache-strategies.md)** - Understand each strategy
- **[API Usage Examples](./05-api-usage.md)** - Start using the API
- **[Auto-Revalidation Details](./06-auto-revalidation.md)** - Background renewal explained

---

**Tip:** Start with defaults and adjust based on your requirements. The defaults are production-ready!
