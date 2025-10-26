<?php

declare(strict_types=1);

namespace Akira\Packagist\Facades;

use Akira\Packagist\PackagistManager;
use Illuminate\Support\Facades\Facade;

/**
 * @see PackagistManager
 */
final class Packagist extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PackagistManager::class;
    }
}
