<?php

declare(strict_types=1);

use Akira\Packagist\Cache\ForeverCache;

describe('ForeverCache', function (): void {
    test('exists', function (): void {
        expect(class_exists(ForeverCache::class))->toBeTrue();
    });
});
