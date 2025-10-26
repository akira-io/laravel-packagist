<?php

declare(strict_types=1);

use Akira\Packagist\Cache\ForeverCache;
use Akira\Packagist\Cache\RememberCache;
use Akira\Packagist\Cache\RevalidateCache;

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Global cache configuration for all Packagist requests.
    | Defines which cache driver to use, revalidation strategy, and default TTL.
    |
    */
    'use' => [
        /*
        |----------------------------------------------------------------------
        | Cache Driver
        |----------------------------------------------------------------------
        |
        | Specifies which cache driver will be used. If not specified,
        | uses the default Laravel driver (usually 'file' or 'redis').
        |
        | Supported values: null, 'file', 'redis', 'memcached', 'database'
        | Default: null (uses application's default driver)
        |
        */
        'driver' => env('PACKAGIST_CACHE_DRIVER'),

        /*
        |----------------------------------------------------------------------
        | Cache Strategy
        |----------------------------------------------------------------------
        |
        | Defines which cache strategy will be used globally.
        |
        | Available strategies:
        |   - RevalidateCache   : Remember with automatic revalidation (DEFAULT)
        |   - RememberCache     : Remember with fixed TTL (3600s)
        |   - ForeverCache      : Indefinite cache until manual cleanup
        |   - NoneCache         : No caching (always makes request)
        |
        | You can also pass a custom class that implements
        | the Akira\Packagist\Contracts\CacheContract interface
        |
        */
        'strategy' => RevalidateCache::class,

        /*
        |----------------------------------------------------------------------
        | Cache TTL (Time To Live)
        |----------------------------------------------------------------------
        |
        | Time in seconds that data will be kept in cache.
        | This is the default TTL for all actions, unless overridden
        | in 'per_action'.
        |
        | Recommended values:
        |   - 300   (5 minutes)   : data that changes frequently
        |   - 3600  (1 hour)      : data with moderate changes
        |   - 28800 (8 hours)     : stable data (DEFAULT)
        |   - 86400 (1 day)       : very stable data
        |
        | Default: 28800 (8 hours)
        |
        */
        'ttl' => (int) env('PACKAGIST_CACHE_TTL', 28800),

        /*
        |----------------------------------------------------------------------
        | Cache Tags
        |----------------------------------------------------------------------
        |
        | Tags to group cache entries and clear them together.
        | Useful when you want to invalidate all Packagist caches
        | at once.
        |
        | Example: cache()->tags('packagist')->flush();
        |
        */
        'tags' => ['packagist'],

        /*
        |----------------------------------------------------------------------
        | Cache Enabled
        |----------------------------------------------------------------------
        |
        | Determines if caching is enabled globally.
        | When disabled, all requests go directly to the API.
        |
        | Useful for debugging or during development.
        |
        | Default: true
        |
        */
        'enabled' => (bool) env('PACKAGIST_CACHE_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Revalidation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic background cache revalidation.
    |
    | When enabled, before the cache expires (based on TTL),
    | a job is dispatched to the queue to revalidate (renew) the cache
    | automatically. This way, when the request arrives, the cache
    | is already fresh.
    |
    | Example: If TTL=3600 and revalidate_before_expiry=300,
    | the cache will be renewed 5 minutes before expiring.
    |
    */
    'auto_revalidation' => [
        /*
        |----------------------------------------------------------------------
        | Enable Auto Revalidation
        |----------------------------------------------------------------------
        |
        | Enables or disables automatic background cache revalidation.
        |
        | When enabled:
        |   - Cache expires normally after TTL
        |   - A job is scheduled X seconds before (revalidate_before_expiry)
        |   - Job makes API request and renews the cache
        |   - When request arrives, cache is already fresh
        |
        | Benefits:
        |   - Zero latency for users
        |   - Data always fresh in cache
        |   - Reduces API load
        |
        | Requires: Queue/Jobs configured in Laravel
        |
        | Default: true
        |
        */
        'enabled' => (bool) env('PACKAGIST_AUTO_REVALIDATION', true),

        /*
        |----------------------------------------------------------------------
        | Revalidate Before Expiry
        |----------------------------------------------------------------------
        |
        | Time in seconds BEFORE the cache expires when automatic
        | revalidation will be triggered.
        |
        | Example:
        |   TTL = 3600 (1 hour)
        |   revalidate_before_expiry = 300 (5 minutes)
        |   → Job will be scheduled after 3300 seconds (55 minutes)
        |   → Cache will be renewed 5 minutes before expiring
        |
        | Recommended values:
        |   - 60   (1 minute)   : for short duration cache
        |   - 300  (5 minutes)  : for moderate cache (DEFAULT)
        |   - 600  (10 minutes) : for long duration cache
        |
        | Default: 300 (5 minutes)
        |
        */
        'revalidate_before_expiry' => (int) env('PACKAGIST_REVALIDATE_BEFORE_EXPIRY', 300),

        /*
        |----------------------------------------------------------------------
        | Queue Name
        |----------------------------------------------------------------------
        |
        | Queue where revalidation jobs will be dispatched.
        | Must correspond to a queue configured in config/queue.php
        |
        | Recommended values:
        |   - 'default'  : uses the default queue
        |   - 'low'      : low priority queue (RECOMMENDED)
        |   - 'high'     : high priority queue
        |
        | Default: 'default'
        |
        */
        'queue' => env('PACKAGIST_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Per-Action Configuration
    |--------------------------------------------------------------------------
    |
    | Action/endpoint specific configurations.
    | Overrides global configurations in 'use' for specific actions.
    |
    | Useful for applying different TTLs and strategies based on data nature
    | (e.g., package details change rarely, search changes frequently).
    |
    */
    'per_action' => [
        /*
        |----------------------------------------------------------------------
        | GetPackageAction
        |----------------------------------------------------------------------
        |
        | Retrieves detailed information for a specific package.
        |
        | Characteristics:
        |   - Data changes rarely (only when new release is published)
        |   - Once loaded, remains stable for long periods
        |   - High computational cost to recalculate
        |
        | Recommended configuration:
        |   - ForeverCache: indefinite caching
        |   - No TTL: data persists until manual cleanup
        |
        | Manual cleanup: cache()->tags('packagist')->flush();
        |
        */
        'GetPackageAction' => [
            'strategy' => ForeverCache::class,
            // No TTL specified: uses global default (28800)
        ],

        /*
        |----------------------------------------------------------------------
        | SearchPackagesAction
        |----------------------------------------------------------------------
        |
        | Searches for packages with criteria (name, type, tags, etc).
        |
        | Characteristics:
        |   - Results can change frequently (new packages)
        |   - Users expect "fresher" results
        |   - Less expensive than GetPackageAction
        |
        | Recommended configuration:
        |   - RememberCache: standard remember with short TTL
        |   - TTL: 300 seconds (5 minutes)
        |   - Auto-revalidation: renews every ~4 minutes 55 seconds
        |
        */
        'SearchPackagesAction' => [
            'strategy' => RememberCache::class,
            'ttl' => 300, // 5 minutes
        ],

        /*
        |----------------------------------------------------------------------
        | GetStatsAction (Example of additional configuration)
        |----------------------------------------------------------------------
        |
        | Example of how to configure other actions if needed.
        | Uncomment to use.
        |
        | 'GetStatsAction' => [
        |     'strategy' => RememberCache::class,
        |     'ttl' => 3600, // 1 hour
        | ],
        |
        */

        /*
        |----------------------------------------------------------------------
        | GetMaintainersAction (Example of additional configuration)
        |----------------------------------------------------------------------
        |
        | Example of how to configure other actions if needed.
        | Uncomment to use.
        |
        | 'GetMaintainersAction' => [
        |     'strategy' => ForeverCache::class,
        | ],
        |
        */
    ],
];
