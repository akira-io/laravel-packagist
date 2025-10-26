<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Validators\StatsValidator;

final class GetStatsAction
{
    public function __construct(
        private readonly ClientContract $client,
        private readonly CacheContract $cache,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters = []): array
    {
        StatsValidator::validateOrFail($filters);

        $cacheKey = 'packagist:stats:'.md5(json_encode($filters));

        return $this->cache->get(
            $cacheKey,
            fn () => $this->client->get('/stats.json'),
            action: self::class
        );
    }
}
