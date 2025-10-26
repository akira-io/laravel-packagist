<?php

declare(strict_types=1);

namespace Akira\Packagist\Facades;

use Akira\Packagist\DTOs\PackageDTO;
use Akira\Packagist\PackagistManager;
use Illuminate\Support\Facades\Facade;

/**
 * @see PackagistManager
 *
 * @method static PackageDTO package(string $name)
 * @method static array search(string $query, array $filters = [])
 * @method static array stats(array $filters = [])
 * @method static array maintainers(string $package)
 * @method static array packages()
 * @method static array topPackages(int $limit = 9)
 * @method static array vendorPackages(string $vendor)
 * @method static array vendorTopPackages(string $vendor, int $limit = 9)
 */
final class Packagist extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PackagistManager::class;
    }
}
