<?php

declare(strict_types=1);

namespace Akira\Packagist\Jobs;

use Akira\Packagist\Contracts\ClientContract;

/**
 * Job para revalidar cache de forma assíncrona em background
 */
final class RevalidateCacheJob
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        private readonly string $cacheKey,
        private readonly string $endpoint,
        private readonly array $context = [],
    ) {}

    public function handle(ClientContract $client): void
    {
        try {
            $data = $client->get($this->endpoint);

            cache()->put(
                $this->cacheKey,
                $data,
                now()->addSeconds($this->context['ttl'] ?? 3600)
            );
        } catch (\Exception $exception) {
            \Log::warning("Packagist cache revalidation failed for {$this->cacheKey}", [
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
