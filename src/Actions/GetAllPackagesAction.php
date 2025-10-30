<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Support\Endpoints;

final readonly class GetAllPackagesAction
{
    public function __construct(
        private ClientContract $client,
        private CacheContract $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $cacheKey = 'packagist:all-packages';

        return $this->cache->get(
            $cacheKey,
            fn (): mixed => $this->client->get(Endpoints::allPackages()),
            action: self::class,
            endpoint: Endpoints::allPackages()
        );
    }
}
