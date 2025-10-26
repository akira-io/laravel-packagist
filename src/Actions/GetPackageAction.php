<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\DTOs\PackageDTO;
use Akira\Packagist\Support\Endpoints;
use Akira\Packagist\Validators\PackageValidator;

final readonly class GetPackageAction
{
    public function __construct(
        private ClientContract $client,
        private CacheContract $cache,
    ) {}

    public function handle(string $package): PackageDTO
    {
        PackageValidator::validateOrFail($package);

        $cacheKey = "packagist:package:{$package}";
        $endpoint = Endpoints::package($package);

        $data = $this->cache->get(
            $cacheKey,
            fn () => $this->client->get($endpoint),
            action: self::class,
            endpoint: $endpoint
        );

        return PackageDTO::fromArray($data);
    }
}
