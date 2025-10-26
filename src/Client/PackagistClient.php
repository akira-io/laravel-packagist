<?php

declare(strict_types=1);

namespace Akira\Packagist\Client;

use Akira\Packagist\Contracts\ClientContract;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Container\Attributes\Singleton;

#[Singleton]
final class PackagistClient implements ClientContract
{
    private const string BASE_URL = 'https://packagist.org';

    private Client $httpClient;

    public function __construct(?Client $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new Client([
            'base_uri' => self::BASE_URL,
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    public function get(string $endpoint): mixed
    {
        try {
            $response = $this->httpClient->get($endpoint);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $exception) {
            throw new \RuntimeException(
                "Failed to fetch from Packagist API: {$exception->getMessage()}",
                (int) $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(string $query, array $filters = []): mixed
    {
        $params = ['q' => $query];

        if (isset($filters['type'])) {
            $params['type'] = $filters['type'];
        }

        if (isset($filters['tags'])) {
            $params['tags'] = $filters['tags'];
        }

        if (isset($filters['sort'])) {
            $params['sort'] = $filters['sort'];
        }

        try {
            $response = $this->httpClient->get('/search.json', [
                'query' => $params,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $exception) {
            throw new \RuntimeException(
                "Failed to search packages: {$exception->getMessage()}",
                (int) $exception->getCode(),
                $exception
            );
        }
    }
}
