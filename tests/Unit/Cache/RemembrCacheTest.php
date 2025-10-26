<?php

declare(strict_types=1);

use Akira\Packagist\Cache\RememberCache;

describe('RememberCache', function (): void {
    test('exists', function (): void {
        expect(class_exists(RememberCache::class))->toBeTrue();
    });
});
