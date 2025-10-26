<?php

declare(strict_types=1);

use Akira\Packagist\Validators\PackageValidator;

describe('PackageValidator', function (): void {
    test('validates correct package names', function (string $packageName): void {
        expect(PackageValidator::validate($packageName))->toBeTrue();
    })->with([
        'laravel/framework',
        'symfony/console',
        'monolog/monolog',
        'phpunit/phpunit',
        'nesbot/carbon',
    ]);

    test('rejects invalid package names', function (string $packageName): void {
        expect(PackageValidator::validate($packageName))->toBeFalse();
    })->with([
        'invalid',
        '/framework',
        'laravel/',
        'laravel//framework',
        'laravel/framework/extra',
    ]);

    test('throws exception on invalid package name', function (): void {
        PackageValidator::validateOrFail('invalid');
    })->throws(InvalidArgumentException::class);

    test('does not throw exception on valid package name', function (): void {
        expect(fn () => PackageValidator::validateOrFail('laravel/framework'))
            ->not->toThrow(InvalidArgumentException::class);
    });
});
