<?php

declare(strict_types=1);

use Akira\Packagist\Cache\AutoRevalidationTrait;

final class AutoRevalidationStub
{
    use AutoRevalidationTrait;

    public function isAutoRevalidationEnabled(): bool
    {
        return $this->autoRevalidationEnabled;
    }
}

describe('AutoRevalidation', function (): void {
    test('trait exists', function (): void {
        expect(trait_exists(AutoRevalidationTrait::class))->toBeTrue();
    });

    test('can enable auto revalidation', function (): void {
        $stub = new AutoRevalidationStub();

        $result = $stub->enableAutoRevalidation();

        expect($result)->toBe($stub)
            ->and($stub->isAutoRevalidationEnabled())->toBeTrue();
    });

    test('can disable auto revalidation', function (): void {
        $stub = new AutoRevalidationStub();
        $stub->enableAutoRevalidation();

        $stub->disableAutoRevalidation();

        expect($stub->isAutoRevalidationEnabled())->toBeFalse();
    });
});
