<?php

declare(strict_types=1);

namespace Akira\Packagist\Validators;

final class StatsValidator
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public static function validate(array $filters = []): bool
    {
        if (! isset($filters['period'])) {
            return true;
        }

        $validPeriods = ['total', 'yearly', 'monthly', 'daily'];

        return in_array($filters['period'], $validPeriods, true);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function validateOrFail(array $filters = []): void
    {
        if (! self::validate($filters)) {
            throw new \InvalidArgumentException(
                'Invalid statistics parameters'
            );
        }
    }
}
