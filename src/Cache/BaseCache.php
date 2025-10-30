<?php

declare(strict_types=1);

namespace Akira\Packagist\Cache;

use Akira\Packagist\Contracts\CacheContract;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;

abstract class BaseCache implements CacheContract
{
    use AutoRevalidationTrait;

    protected Repository $store;

    /**
     * @param  array<int, string>  $tags
     */
    public function __construct(
        protected readonly Factory $cacheFactory,
        protected ?string $driver = null,
        protected array $tags = [],
        protected int $ttl = 0,
    ) {
        $this->store = $this->resolveStore();
    }

    public function forget(string $key): void
    {
        $this->store->forget($key);
    }

    protected function resolveStore(): Repository
    {
        $repository = $this->driver === null
            ? $this->cacheFactory->store()
            : $this->cacheFactory->store($this->driver);

        if ($this->tags === []) {
            return $repository;
        }

        if ($this->driverSupportsTagging()) {

            return $repository->tags($this->tags);
        }

        return $repository;
    }

    protected function resolveTtl(int $ttl): int
    {
        return $ttl > 0 ? $ttl : $this->ttl;
    }

    private function driverSupportsTagging(): bool
    {
        $supportedDrivers = ['redis', 'memcached', 'database', 'dynamodb'];

        return in_array($this->driver, $supportedDrivers, true);
    }
}
