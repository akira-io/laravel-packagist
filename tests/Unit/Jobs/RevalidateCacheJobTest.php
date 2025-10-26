<?php

declare(strict_types=1);

use Akira\Packagist\Jobs\RevalidateCacheJob;

describe('RevalidateCacheJob', function (): void {
    test('can be instantiated', function (): void {
        $job = new RevalidateCacheJob(
            'cache_key',
            '/endpoint'
        );

        expect($job)->toBeInstanceOf(RevalidateCacheJob::class);
    });

    test('accepts context parameters', function (): void {
        $context = ['ttl' => 3600, 'action' => 'GetPackageAction'];

        $job = new RevalidateCacheJob(
            'cache_key',
            '/endpoint',
            $context
        );

        expect($job)->toBeInstanceOf(RevalidateCacheJob::class);
    });
});
