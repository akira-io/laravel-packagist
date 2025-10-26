<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Support\Endpoints;

final readonly class GetTopPackagesAction
{
    public function __construct(
        private ClientContract $client,
        private CacheContract $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(int $limit = 9): array
    {
        $cacheKey = "packagist:top-packages:{$limit}";

        return $this->cache->get(
            $cacheKey,
            fn () => $this->fetchTopPackages($limit),
            ttl: 3600,
            action: self::class,
            endpoint: Endpoints::allPackages()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchTopPackages(int $limit): array
    {
        $response = $this->client->get('/packages/list.json');

        if (! isset($response['packages']) || ! is_array($response['packages'])) {
            return [];
        }

        $packages = [];
        foreach (array_slice($response['packages'], 0, $limit * 2) as $packageName) {
            try {
                $packageData = $this->client->get("/packages/{$packageName}.json");
                if (isset($packageData['package'])) {
                    $packages[] = [
                        'name' => $packageData['package']['name'],
                        'description' => $packageData['package']['description'] ?? '',
                        'downloads' => $packageData['package']['downloads']['total'] ?? 0,
                        'url' => $packageData['package']['repository'] ?? '',
                    ];
                }
            } catch (\Exception) {
                continue;
            }
        }

        usort($packages, fn ($a, $b) => $b['downloads'] <=> $a['downloads']);

        return array_slice($packages, 0, $limit);
    }
}
