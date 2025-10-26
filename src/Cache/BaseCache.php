<?php

declare(strict_types=1);

namespace Akira\Packagist\Cache;

use Akira\Packagist\Contracts\CacheContract;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;

/**
 * @psalm-type CacheRepository = Repository
 */
abstract class BaseCache implements CacheContract
{
    protected Repository $store;

    protected ?string $driver = null;

    /**
     * @var array<int, string>
     */
    protected array $tags = [];

    protected int $ttl = 0;

    /**
     * @param  array<int, string>  $tags
     */
    public function __construct(
        protected readonly Factory $cacheFactory,
        ?string $driver = null,
        array $tags = [],
        int $ttl = 0,
    ) {
        $this->driver = $driver;
        $this->tags = $tags;
        $this->ttl = $ttl;
        $this->store = $this->resolveStore();
    }

    protected function resolveStore(): Repository
    {
        $repository = $this->driver === null
            ? $this->cacheFactory->store()
            : $this->cacheFactory->store($this->driver);

        if (empty($this->tags)) {
            return $repository;
        }

        return $repository->tags($this->tags);
    }

    protected function resolveTtl(int $ttl): int
    {
        return $ttl > 0 ? $ttl : $this->ttl;
    }

    public function forget(string $key): void
    {
        $this->store->forget($key);
    }
}
