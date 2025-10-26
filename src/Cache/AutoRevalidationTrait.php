<?php

declare(strict_types=1);

namespace Akira\Packagist\Cache;

use Akira\Packagist\Jobs\RevalidateCacheJob;

/**
 * Trait para adicionar auto-revalidação de cache em background
 */
trait AutoRevalidationTrait
{
    protected bool $autoRevalidationEnabled = false;

    protected int $revalidateBeforeExpiry = 300;

    protected string $queueName = 'default';

    public function enableAutoRevalidation(
        int $revalidateBeforeExpiry = 300,
        string $queueName = 'default'
    ): self {
        $this->autoRevalidationEnabled = true;
        $this->revalidateBeforeExpiry = $revalidateBeforeExpiry;
        $this->queueName = $queueName;

        return $this;
    }

    public function disableAutoRevalidation(): self
    {
        $this->autoRevalidationEnabled = false;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function scheduleRevalidation(
        string $cacheKey,
        string $endpoint,
        array $context = []
    ): void {
        if (! $this->autoRevalidationEnabled) {
            return;
        }

        if (! function_exists('dispatch')) {
            return;
        }

        $delaySeconds = max(1, $this->revalidateBeforeExpiry);

        dispatch(new RevalidateCacheJob($cacheKey, $endpoint, $context))
            ->onQueue($this->queueName)
            ->delay(now()->addSeconds($delaySeconds));
    }
}
