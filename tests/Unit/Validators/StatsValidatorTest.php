<?php

declare(strict_types=1);

use Akira\Packagist\Validators\StatsValidator;

describe('StatsValidator', function (): void {
    test('validates empty filters', function (): void {
        expect(StatsValidator::validate())->toBeTrue();
    });

    test('validates valid period', function (string $period): void {
        expect(StatsValidator::validate(['period' => $period]))->toBeTrue();
    })->with([
        'total',
        'yearly',
        'monthly',
        'daily',
    ]);

    test('rejects invalid period', function (): void {
        expect(StatsValidator::validate(['period' => 'invalid']))->toBeFalse();
    });

    test('throws exception on invalid period', function (): void {
        StatsValidator::validateOrFail(['period' => 'invalid']);
    })->throws(InvalidArgumentException::class);
});
