<?php

declare(strict_types=1);

use Akira\Packagist\Cache\AutoRevalidationTrait;

describe('AutoRevalidation', function (): void {
    test('trait exists', function (): void {
        expect(trait_exists(AutoRevalidationTrait::class))->toBeTrue();
    });

    test('can enable auto revalidation', function (): void {
        $stub = new class
        {
            use AutoRevalidationTrait;
        };

        $result = $stub->enableAutoRevalidation(300, 'default');

        expect($result)->toBe($stub);
        expect($stub->autoRevalidationEnabled)->toBeTrue();
    });

    test('can disable auto revalidation', function (): void {
        $stub = new class
        {
            use AutoRevalidationTrait;

            public bool $autoRevalidationEnabled = true;
        };

        $stub->disableAutoRevalidation();

        expect($stub->autoRevalidationEnabled)->toBeFalse();
    });
});
