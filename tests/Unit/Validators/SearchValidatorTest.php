<?php

declare(strict_types=1);

use Akira\Packagist\Validators\SearchValidator;

describe('SearchValidator', function (): void {
    test('validates non-empty query', function (): void {
        expect(SearchValidator::validate('laravel'))->toBeTrue();
    });

    test('rejects empty query', function (): void {
        expect(SearchValidator::validate(''))->toBeFalse();
    });

    test('rejects whitespace-only query', function (): void {
        expect(SearchValidator::validate('   '))->toBeFalse();
    });

    test('rejects query exceeding 1000 characters', function (): void {
        $longQuery = str_repeat('a', 1001);
        expect(SearchValidator::validate($longQuery))->toBeFalse();
    });

    test('accepts query within 1000 characters', function (): void {
        $query = str_repeat('a', 1000);
        expect(SearchValidator::validate($query))->toBeTrue();
    });

    test('throws exception on invalid query', function (): void {
        SearchValidator::validateOrFail('');
    })->throws(InvalidArgumentException::class);
});
