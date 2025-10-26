<?php

declare(strict_types=1);

use Akira\Packagist\DTOs\PackageDTO;

describe('PackageDTO', function (): void {
    test('creates dto from array', function (): void {
        $data = [
            'package' => [
                'name' => 'laravel/framework',
                'description' => 'The Laravel Framework',
                'repository' => 'https://github.com/laravel/framework',
                'homepage' => 'https://laravel.com',
                'license' => 'MIT',
                'downloads' => 1000000,
                'favers' => 500000,
                'versions' => [],
                'maintainers' => [],
            ],
        ];

        $dto = PackageDTO::fromArray($data);

        expect($dto->name)->toBe('laravel/framework');
        expect($dto->description)->toBe('The Laravel Framework');
        expect($dto->repository)->toBe('https://github.com/laravel/framework');
        expect($dto->downloads)->toBe(1000000);
        expect($dto->favers)->toBe(500000);
    });

    test('converts dto to array', function (): void {
        $dto = new PackageDTO(
            name: 'laravel/framework',
            description: 'The Laravel Framework',
            repository: 'https://github.com/laravel/framework',
            downloads: 1000000,
            favers: 500000,
        );

        $array = $dto->toArray();

        expect($array['name'])->toBe('laravel/framework');
        expect($array['description'])->toBe('The Laravel Framework');
        expect($array['downloads'])->toBe(1000000);
    });

    test('handles missing optional fields', function (): void {
        $data = [
            'name' => 'laravel/framework',
            'description' => 'The Laravel Framework',
        ];

        $dto = PackageDTO::fromArray($data);

        expect($dto->repository)->toBeNull();
        expect($dto->homepage)->toBeNull();
        expect($dto->downloads)->toBe(0);
    });
});
