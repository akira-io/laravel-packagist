<?php

declare(strict_types=1);

namespace Akira\Packagist\Cache;

final class RememberCache extends BaseCache
{
    public function get(string $key, callable $callback, int $ttl = 0, ?string $action = null): mixed
    {
        $resolvedTtl = $this->resolveTtl($ttl);

        return $this->store->remember(
            $key,
            $resolvedTtl > 0 ? $resolvedTtl : 3600,
            $callback
        );
    }
}
