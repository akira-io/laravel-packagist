<?php

declare(strict_types=1);

describe('PackagistManager', function (): void {
    test('exists', function (): void {
        expect(class_exists(\Akira\Packagist\PackagistManager::class))->toBeTrue();
    });
});
