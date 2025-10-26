<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Support\Endpoints;
use Akira\Packagist\Validators\SearchValidator;

final readonly class GetVendorPackagesAction
{
    public function __construct(
        private ClientContract $client,
        private CacheContract $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(string $vendor): array
    {
        $vendor = strtolower(trim($vendor));
        SearchValidator::validateOrFail($vendor);

        $cacheKey = "packagist:vendor:{$vendor}";

        return $this->cache->get(
            $cacheKey,
            fn () => $this->client->search("vendor:{$vendor}"),
            action: self::class,
            endpoint: Endpoints::search()
        );
    }
}
