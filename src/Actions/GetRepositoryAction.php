<?php

declare(strict_types=1);

namespace Akira\Packagist\Actions;

use Akira\Packagist\Contracts\CacheContract;
use Akira\Packagist\Contracts\ClientContract;
use Akira\Packagist\Support\Endpoints;
use Akira\Packagist\Validators\PackageValidator;

final readonly class GetRepositoryAction
{
    public function __construct(
        private ClientContract $client,
        private CacheContract $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(string $package): array
    {
        PackageValidator::validateOrFail($package);

        $cacheKey = "packagist:repository:{$package}";
        $endpoint = Endpoints::package($package);

        $data = $this->cache->get(
            $cacheKey,
            fn (): mixed => $this->client->get($endpoint),
            action: self::class,
            endpoint: $endpoint
        );

        return $this->extractRepositoryInfo($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function extractRepositoryInfo(array $data): array
    {
        $package = $data['package'] ?? [];

        $repositoryUrl = $package['repository'] ?? null;
        $sourceUrl = $this->extractGithubUrl($package);

        return [
            'name' => $package['name'] ?? null,
            'repository' => $repositoryUrl,
            'source_url' => $sourceUrl,
            'github_owner' => $this->extractGithubOwner($sourceUrl),
            'github_repo' => $this->extractGithubRepo($sourceUrl),
        ];
    }

    private function extractGithubUrl(array $package): ?string
    {
        if (! empty($package['repository'])) {
            return $package['repository'];
        }

        $versions = $package['versions'] ?? [];
        if (! empty($versions)) {
            $latestVersion = reset($versions);
            if (is_array($latestVersion) && isset($latestVersion['source']['url'])) {
                return $latestVersion['source']['url'];
            }
        }

        return null;
    }

    private function extractGithubOwner(?string $repositoryUrl): ?string
    {
        if (! $repositoryUrl) {
            return null;
        }

        // Handle both https://github.com/owner/repo and git@github.com:owner/repo formats
        if (preg_match('#(?:https://github\.com/|git@github\.com:)([^/]+)/#', $repositoryUrl, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractGithubRepo(?string $repositoryUrl): ?string
    {
        if (! $repositoryUrl) {
            return null;
        }

        // Handle both https://github.com/owner/repo and git@github.com:owner/repo formats
        if (preg_match('#(?:https://github\.com/|git@github\.com:)[^/]+/([^/]+?)(?:\.git)?$#', $repositoryUrl, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
