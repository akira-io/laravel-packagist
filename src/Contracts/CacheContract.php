<?php

declare(strict_types=1);

namespace Akira\Packagist\Contracts;

interface CacheContract
{
    public function get(string $key, callable $callback, int $ttl = 0, ?string $action = null): mixed;

    public function forget(string $key): void;
}