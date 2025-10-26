<?php

declare(strict_types=1);

namespace Akira\Packagist\Contracts;

interface ClientContract
{
    public function get(string $endpoint): mixed;

    public function search(string $query, array $filters = []): mixed;
}
