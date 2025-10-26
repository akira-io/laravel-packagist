<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Support\Endpoints;
use Akira\Packagist\Validators\StatsValidator;

final readonly class GetStatsAction
{
    public function __construct(
        private ClientContract $client,
        private CacheContract $cache,
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
            fn () => $this->client->get(Endpoints::stats()),
            action: self::class,
            endpoint: Endpoints::stats()
        );
    }
}
