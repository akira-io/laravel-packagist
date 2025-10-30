<?php

declare(strict_types=1);

namespace Akira\Packagist\Validators;

final class SearchValidator
{
    public static function validate(string $query): bool
    {
        if (in_array(trim($query), ['', '0'], true)) {
            return false;
        }

        return strlen($query) <= 1000;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function validateOrFail(string $query, array $filters = []): void
    {
        if (! self::validate($query, $filters)) {
            throw new \InvalidArgumentException(
                'Invalid search query or filters'
            );
        }
    }
}
