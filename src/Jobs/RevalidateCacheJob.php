<?php

declare(strict_types=1);

namespace Akira\Packagist\Jobs;

use Akira\Packagist\Contracts\ClientContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

final class RevalidateCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        private readonly string $cacheKey,
        private readonly string $endpoint,
        private readonly array $context = [],
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

            $ttlValue = $this->context['ttl'] ?? 3600;
            $ttl = is_int($ttlValue) ? $ttlValue : (int) $ttlValue;

            cache()->put(
                $this->cacheKey,
                $data,
                now()->addSeconds($ttl)
            );
        } catch (\Exception) {
            logger()->warning('Packagist cache revalidation failed for '.$this->cacheKey);
        }
    }
}
