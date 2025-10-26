<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Support\Endpoints;
use Akira\Packagist\Validators\PackageValidator;

final readonly class GetMaintainersAction
{
    public function __construct(
        private ClientContract $client,
        private CacheContract $cache,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(string $package): array
    {
        PackageValidator::validateOrFail($package);

        $cacheKey = "packagist:maintainers:{$package}";
        $endpoint = Endpoints::package($package);

        $data = $this->cache->get(
            $cacheKey,
            fn () => $this->client->get($endpoint),
            action: self::class,
            endpoint: $endpoint
        );

        return $data['package']['maintainers'] ?? [];
    }
}
