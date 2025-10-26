<?php

declare(strict_types=1);

use Akira\Packagist\Cache\ForeverCache;
use Akira\Packagist\Cache\RememberCache;
use Akira\Packagist\Cache\RevalidateCache;

return [
    'use' => [
        'driver' => env('PACKAGIST_CACHE_DRIVER'),
        'strategy' => RevalidateCache::class,
        'ttl' => (int) env('PACKAGIST_CACHE_TTL', 3600),
        'tags' => ['packagist'],
        'enabled' => (bool) env('PACKAGIST_CACHE_ENABLED', true),
    ],

    'per_action' => [
        'GetPackageAction' => [
            'strategy' => ForeverCache::class,
        ],
        'SearchPackagesAction' => [
            'strategy' => RememberCache::class,
            'ttl' => 300,
        ],
    ],
];
