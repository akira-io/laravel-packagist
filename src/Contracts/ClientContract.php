<?php

declare(strict_types=1);

namespace Akira\Packagist\Contracts;

use Akira\Packagist\Client\PackagistClient;
use Illuminate\Container\Attributes\Bind;

#[Bind(PackagistClient::class)]
interface ClientContract
{
    public function get(string $endpoint): mixed;

    public function search(string $query, array $filters = []): mixed;
}
