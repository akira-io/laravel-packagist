<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Support\Endpoints;
use Akira\Packagist\Validators\SearchValidator;

final readonly class GetVendorPackagesAction
{
    public function __construct(
        private ClientContract $client,
        private CacheContract $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(string $vendor): array
    {
        $vendor = strtolower(trim($vendor));
        SearchValidator::validateOrFail($vendor);

        $cacheKey = "packagist:vendor:{$vendor}";

        return $this->cache->get(
            $cacheKey,
            fn () => $this->fetchVendorPackages($vendor),
            action: self::class,
            endpoint: Endpoints::search()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchVendorPackages(string $vendor): array
    {
        // Search for vendor name instead of using vendor: filter
        $response = $this->client->search($vendor, ['per_page' => 100]);

        if (! isset($response['results']) || ! is_array($response['results'])) {
            return ['results' => [], 'total' => 0];
        }

        // Filter only packages that start with vendor/
        $vendorPrefix = strtolower($vendor) . '/';
        $filtered = array_filter($response['results'], function ($package) use ($vendorPrefix) {
            return str_starts_with(strtolower($package['name'] ?? ''), $vendorPrefix);
        });

        return [
            'results' => array_values($filtered),
            'total' => count($filtered),
        ];
    }
}
