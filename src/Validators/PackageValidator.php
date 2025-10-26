<?php

declare(strict_types=1);

namespace Akira\Packagist\Validators;


final class PackageValidator
{
    public static function validate(string $package): bool
    {
        return (bool) preg_match(
            '/^[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9\-\.]*[a-z0-9])?$/i',
            $packagen
        );
    }

    public static function validateOrFail(string $package): void
    {
        if (! self::validate($package)) {
            throw new \InvalidArgumentException(
                "Invalid package name format: {$package}"
            );
        }
    }
}