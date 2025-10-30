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
                'downloads' => [
                    'total' => 1000000,
                    'monthly' => 50000,
                    'daily' => 1000,
                ],
                'favers' => 500000,
                'versions' => [],
                'maintainers' => [],
            ],
        ];

        $dto = PackageDTO::fromArray($data);

        expect($dto->name)->toBe('laravel/framework')
            ->and($dto->description)->toBe('The Laravel Framework')
            ->and($dto->repository)->toBe('https://github.com/laravel/framework')
            ->and($dto->downloads)->toBe(['total' => 1000000, 'monthly' => 50000, 'daily' => 1000])
            ->and($dto->favers)->toBe(500000);
    });

    test('converts dto to array', function (): void {
        $downloads = ['total' => 1000000, 'monthly' => 50000, 'daily' => 1000];
        $dto = new PackageDTO(
            name: 'laravel/framework',
            description: 'The Laravel Framework',
            repository: 'https://github.com/laravel/framework',
            downloads: $downloads,
            favers: 500000,
        );

        $array = $dto->toArray();

        expect($array['name'])->toBe('laravel/framework')
            ->and($array['description'])->toBe('The Laravel Framework')
            ->and($array['downloads'])->toBe($downloads);
    });

    test('handles missing optional fields', function (): void {
        $data = [
            'name' => 'laravel/framework',
            'description' => 'The Laravel Framework',
        ];

        $dto = PackageDTO::fromArray($data);

        expect($dto->repository)->toBeNull()
            ->and($dto->homepage)->toBeNull()
            ->and($dto->downloads)->toBe(['total' => 0, 'monthly' => 0, 'daily' => 0]);
    });
});
