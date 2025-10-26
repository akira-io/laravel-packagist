<?php

declare(strict_types=1);

use Akira\Packagist\Cache\NoneCache;

describe('NoneCache', function (): void {
    test('exists', function (): void {
        expect(class_exists(NoneCache::class))->toBeTrue();
    });
});
