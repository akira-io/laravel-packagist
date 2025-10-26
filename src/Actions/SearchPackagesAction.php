<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Validators\SearchValidator;


final class SearchPackagesAction
{
    public function __construct(
        private readonly ClientContract $client,
        private readonly CacheContract $cache,
    ) {}

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function handle(string $query, array $filters = []): array
    {
        SearchValidator::validateOrFail($query, $filters);

        $cacheKey = 'packagist:search:' . md5($query . json_encode($filters));

        return $this->cache->get(
            $cacheKey,
            fn () => $this->client->search($query, $filters),
            action: self::class
        );
    }
}