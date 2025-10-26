<?php

declare(strict_types=1);

use Akira\Packagist\Actions\SearchPackagesAction;

describe('SearchPackagesAction', function (): void {
    test('exists', function (): void {
        expect(class_exists(SearchPackagesAction::class))->toBeTrue();
    });
});
