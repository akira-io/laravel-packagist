<?php

declare(strict_types=1);

namespace Akira\Packagist\Jobs;

use Akira\Packagist\Contracts\ClientContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

final readonly class RevalidateCacheJob implements ShouldQueue
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        private string $cacheKey,
        private string $endpoint,
        private array $context = [],
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping($this->cacheKey)];
    }

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
