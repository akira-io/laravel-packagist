<?php

declare(strict_types=1);

namespace Akira\Packagist\Cache;

final class RevalidateCache extends BaseCache
{
    public function get(string $key, callable $callback, int $ttl = 0, ?string $action = null): mixed
    {
        $resolvedTtl = $this->resolveTtl($ttl);

        $result = $this->store->remember(
            $key,
            $resolvedTtl > 0 ? $resolvedTtl : null,
            $callback
        );

        if ($this->autoRevalidationEnabled && $resolvedTtl > 0) {
            $this->scheduleRevalidation($key, '', [
                'ttl' => $resolvedTtl,
                'action' => $action,
            ]);
        }

        return $result;
    }
}
