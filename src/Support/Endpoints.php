<?php

declare(strict_types=1);

namespace Akira\Packagist\Support;

final class Endpoints
{
    public const string PACKAGE = '/packages/{package}.json';

    public const string SEARCH = '/search.json';

    public const string STATS = '/stats.json';

    public const string ALL_PACKAGES = '/packages/list.json';

    public static function package(string $name): string
    {
        return str_replace('{package}', $name, self::PACKAGE);
    }

    public static function search(): string
    {
        return self::SEARCH;
    }

    public static function stats(): string
    {
        return self::STATS;
    }

    public static function allPackages(): string
    {
        return self::ALL_PACKAGES;
    }
}
