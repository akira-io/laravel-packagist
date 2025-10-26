<?php

declare(strict_types=1);

use Akira\Packagist\Actions\GetPackageAction;

describe('GetPackageAction', function (): void {
    test('exists', function (): void {
        expect(class_exists(GetPackageAction::class))->toBeTrue();
    });
});
