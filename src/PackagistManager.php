<?php

declare(strict_types=1);

namespace Akira\Packagist;

use Akira\Packagist\Actions\GetAllPackagesAction;
use Akira\Packagist\Actions\GetMaintainersAction;
use Akira\Packagist\Actions\GetPackageAction;
use Akira\Packagist\Actions\GetStatsAction;
use Akira\Packagist\Actions\GetTopPackagesAction;
use Akira\Packagist\Actions\GetVendorPackagesAction;
use Akira\Packagist\Actions\GetVendorTopPackagesAction;
use Akira\Packagist\Actions\SearchPackagesAction;
use Akira\Packagist\Client\PackagistClient;
use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\DTOs\PackageDTO;
use Akira\Packagist\Support\CacheFactory;

final class PackagistManager
{
    private ClientContract $client;

    private CacheContract $cache;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config = [],
    ) {
        $this->client = new PackagistClient;
        $this->initializeCache();
    }

    private function initializeCache(): void
    {
        $strategyConfig = $this->config['use'] ?? [];
        $strategyClass = $strategyConfig['strategy'] ?? 'remember';

        $this->cache = CacheFactory::make(
            app('cache'),
            $strategyClass,
            [
                'driver' => $strategyConfig['driver'] ?? null,
                'tags' => $strategyConfig['tags'] ?? [],
                'ttl' => $strategyConfig['ttl'] ?? 3600,
            ]
        );

        $this->initializeAutoRevalidation();
    }

    private function initializeAutoRevalidation(): void
    {
        $autoRevalidationConfig = $this->config['auto_revalidation'] ?? [];

        if (! ($autoRevalidationConfig['enabled'] ?? false)) {
            return;
        }

        if (! method_exists($this->cache, 'enableAutoRevalidation')) {
            return;
        }

        $this->cache->enableAutoRevalidation(
            $autoRevalidationConfig['revalidate_before_expiry'] ?? 300,
            $autoRevalidationConfig['queue'] ?? 'default'
        );
    }

    public function package(string $name): PackageDTO
    {
        return new GetPackageAction($this->client, $this->cache)->handle($name);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function search(string $query, array $filters = []): array
    {
        return new SearchPackagesAction($this->client, $this->cache)->handle($query, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function stats(array $filters = []): array
    {
        return new GetStatsAction($this->client, $this->cache)->handle($filters);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function maintainers(string $package): array
    {
        return new GetMaintainersAction($this->client, $this->cache)->handle($package);
    }

    /**
     * @return array<string, mixed>
     */
    public function packages(): array
    {
        return new GetAllPackagesAction($this->client, $this->cache)->handle();
    }

    /**
     * @return array<string, mixed>
     */
    public function topPackages(int $limit = 9): array
    {
        return new GetTopPackagesAction($this->client, $this->cache)->handle($limit);
    }

    /**
     * @return array<string, mixed>
     */
    public function vendorPackages(string $vendor): array
    {
        return new GetVendorPackagesAction($this->client, $this->cache)->handle($vendor);
    }

    /**
     * @return array<string, mixed>
     */
    public function vendorTopPackages(string $vendor, int $limit = 9): array
    {
        return new GetVendorTopPackagesAction($this->client, $this->cache)->handle($vendor, $limit);
    }

    public function withClient(ClientContract $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function withCache(CacheContract $cache): self
    {
        $this->cache = $cache;

        return $this;
    }
}
