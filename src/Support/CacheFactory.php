<?php

declare(strict_types=1);

namespace Akira\Packagist\Support;

use Akira\Packagist\Cache\ForeverCache;
use Akira\Packagist\Cache\NoneCache;
use Akira\Packagist\Cache\RememberCache;
use Akira\Packagist\Cache\RevalidateCache;
use Akira\Packagist\Contracts\CacheContract;
use Illuminate\Contracts\Cache\Factory;

/**
 * Factory for instantiating cache strategies
 */
final class CacheFactory
{
    /**
     * @var array<string, class-string<CacheContract>>
     */
    private const BUILT_IN_STRATEGIES = [
        'remember' => RememberCache::class,
        'revalidate' => RevalidateCache::class,
        'forever' => ForeverCache::class,
        'none' => NoneCache::class,
    ];

    /**
     * @param  class-string<CacheContract>|string  $strategy
     * @param  array<string, mixed>  $config
     */
    public static function make(
        Factory $cacheFactory,
        string $strategy,
        array $config = [],
    ): CacheContract {
        $strategyClass = self::resolveStrategy($strategy);

        return app()->make($strategyClass, [
            'cacheFactory' => $cacheFactory,
            'driver' => $config['driver'] ?? null,
            'tags' => $config['tags'] ?? [],
            'ttl' => $config['ttl'] ?? 0,
        ]);
    }

    /**
     * @param  class-string<CacheContract>|string  $strategy
     * @return class-string<CacheContract>
     */
    private static function resolveStrategy(string $strategy): string
    {
        if (isset(self::BUILT_IN_STRATEGIES[$strategy])) {
            return self::BUILT_IN_STRATEGIES[$strategy];
        }

        if (class_exists($strategy)) {
            return $strategy;
        }

        throw new \InvalidArgumentException(
            "Cache strategy [{$strategy}] does not exist."
        );
    }
}
