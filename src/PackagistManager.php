<?php

declare(strict_types=1);

namespace Akira\Packagist;

use Akira\Packagist\Actions\GetMaintainersAction;
use Akira\Packagist\Actions\GetPackageAction;
use Akira\Packagist\Actions\GetStatsAction;
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
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config = [],
    ) {
        $this->client = new PackagistClient();
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
    }

    public function package(string $name): PackageDTO
    {
        return (new GetPackageAction($this->client, $this->cache))->handle($name);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function search(string $query, array $filters = []): array
    {
        return (new SearchPackagesAction($this->client, $this->cache))->handle($query, $filters);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function stats(array $filters = []): array
    {
        return (new GetStatsAction($this->client, $this->cache))->handle($filters);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function maintainers(string $package): array
    {
        return (new GetMaintainersAction($this->client, $this->cache))->handle($package);
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