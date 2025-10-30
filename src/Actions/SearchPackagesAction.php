<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Support\Endpoints;
use Akira\Packagist\Validators\SearchValidator;

final readonly class SearchPackagesAction
{
    public function __construct(
        private ClientContract $client,
        private CacheContract $cache,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(string $query, array $filters = []): array
    {
        SearchValidator::validateOrFail($query, $filters);

        $cacheKey = 'packagist:search:'.md5($query.json_encode($filters));

        return $this->cache->get(
            $cacheKey,
            fn (): mixed => $this->client->search($query, $filters),
            action: self::class,
            endpoint: Endpoints::search()
        );
    }
}
