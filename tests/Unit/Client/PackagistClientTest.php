<?php

declare(strict_types=1);

use Akira\Packagist\Client\PackagistClient;

describe('PackagistClient', function (): void {
    test('can be instantiated', function (): void {
        $client = new PackagistClient;
        expect($client)->toBeInstanceOf(PackagistClient::class);
    });
});
