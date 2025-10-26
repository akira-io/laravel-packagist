<?php

declare(strict_types=1);

namespace Akira\Packagist\Validators;


final class SearchValidator
{
    /**
     * @param array<string, mixed> $filters
     */
    public static function validate(string $query, array $filters = []): bool
    {
        if (empty(trim($query))) {
            return false;
        }

        if (strlen($query) > 1000) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $filters
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