<?php

declare(strict_types=1);

namespace Akira\Packagist\Facades;

use Akira\Packagist\PackagistManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Akira\Packagist\DTOs\PackageDTO package(string $name)
 * @method static array search(string $query, array $filters = [])
 * @method static array stats(array $filters = [])
 * @method static array maintainers(string $package)
 * @method static PackagistManager withClient(\Akira\Packagist\Contracts\ClientContract $client)
 * @method static PackagistManager withCache(\Akira\Packagist\Contracts\CacheContract $cache)
 */
final class Packagist extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PackagistManager::class;
    }
}