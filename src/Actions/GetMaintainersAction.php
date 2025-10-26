<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Validators\PackageValidator;


final class GetMaintainersAction
{
    public function __construct(
        private readonly ClientContract $client,
        private readonly CacheContract $cache,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(string $package): array
    {
        PackageValidator::validateOrFail($package);

        $cacheKey = "packagist:maintainers:{$package}";

        $data = $this->cache->get(
            $cacheKey,
            fn () => $this->client->get("/p/{$package}.json"),
            action: self::class
        );

        return $data['package']['maintainers'] ?? [];
    }
}