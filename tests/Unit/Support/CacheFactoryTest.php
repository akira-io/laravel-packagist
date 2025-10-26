<?php

declare(strict_types=1);

describe('CacheFactory', function (): void {
    test('exists', function (): void {
        expect(class_exists('Akira\Packagist\Support\CacheFactory'))->toBeTrue();
    });
});
