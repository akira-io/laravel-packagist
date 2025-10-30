<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Support\Endpoints;
use Akira\Packagist\Validators\SearchValidator;

final readonly class GetVendorTopPackagesAction
{
    public function __construct(
        private ClientContract $client,
        private CacheContract $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(string $vendor, int $limit = 9): array
    {
        $vendor = strtolower(trim($vendor));
        SearchValidator::validateOrFail($vendor);

        $cacheKey = "packagist:vendor-top:{$vendor}:{$limit}";

        return $this->cache->get(
            $cacheKey,
            fn (): array => $this->fetchVendorTopPackages($vendor, $limit),
            ttl: 3600,
            action: self::class,
            endpoint: Endpoints::search()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchVendorTopPackages(string $vendor, int $limit): array
    {
        // Search for vendor name and filter manually
        $response = $this->client->search($vendor, ['per_page' => 100]);

        if (! isset($response['results']) || ! is_array($response['results'])) {
            return [];
        }

        // Filter only packages that start with vendor/
        $vendorPrefix = strtolower($vendor).'/';
        $filtered = array_filter($response['results'], fn (array $package): bool => str_starts_with(strtolower($package['name'] ?? ''), $vendorPrefix));

        // Get packages with most downloads
        $packages = array_map(fn (array $package): array => [
            'name' => $package['name'] ?? '',
            'description' => $package['description'] ?? '',
            'downloads' => $package['downloads'] ?? 0,
            'url' => $package['url'] ?? '',
            'repository' => $package['repository'] ?? '',
        ], $filtered);

        // Sort by downloads descending
        usort($packages, fn (array $a, array $b): int => $b['downloads'] <=> $a['downloads']);

        return array_slice($packages, 0, $limit);
    }
}
