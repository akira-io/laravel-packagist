<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\DTOs\PackageDTO;
use Akira\Packagist\Validators\PackageValidator;

final class GetPackageAction
{
    public function __construct(
        private readonly ClientContract $client,
        private readonly CacheContract $cache,
    ) {}

    public function handle(string $package): PackageDTO
    {
        PackageValidator::validateOrFail($package);

        $cacheKey = "packagist:package:{$package}";

        $data = $this->cache->get(
            $cacheKey,
            fn () => $this->client->get("/p/{$package}.json"),
            action: self::class
        );

        return PackageDTO::fromArray($data);
    }
}
