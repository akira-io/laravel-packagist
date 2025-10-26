<?php

declare(strict_types=1);

namespace Akira\Packagist\Cache;


final class NoneCache extends BaseCache
{
    public function get(string $key, callable $callback, int $ttl = 0, ?string $action = null): mixed
    {
        return $callback();
    }
}