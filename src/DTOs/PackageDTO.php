<?php

declare(strict_types=1);

namespace Akira\Packagist\DTOs;

/**
 * @psalm-immutable
 */
final readonly class PackageDTO
{
    /**
     * @param  array<string, VersionDTO>  $versions
     * @param  array<int, MaintainerDTO>  $maintainers
     */
    public function __construct(
        public string $name,
        public string $description,
        public ?string $repository = null,
        public array $versions = [],
        public array $maintainers = [],
        public ?string $homepage = null,
        public ?string $license = null,
        public int $downloads = 0,
        public int $favers = 0,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $package = (array) ($data['package'] ?? $data);

        $versions = [];
        foreach (($package['versions'] ?? []) as $version => $versionData) {
            $versions[(string) $version] = VersionDTO::fromArray((array) $versionData);
        }

        $maintainers = [];
        foreach (($package['maintainers'] ?? []) as $maintainerData) {
            $maintainers[] = MaintainerDTO::fromArray((array) $maintainerData);
        }

        return new self(
            name: (string) ($package['name'] ?? ''),
            description: (string) ($package['description'] ?? ''),
            repository: isset($package['repository']) ? (string) $package['repository'] : null,
            versions: $versions,
            maintainers: $maintainers,
            homepage: isset($package['homepage']) ? (string) $package['homepage'] : null,
            license: isset($package['license']) ? (string) $package['license'] : null,
            downloads: (int) ($package['downloads'] ?? 0),
            favers: (int) ($package['favers'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'repository' => $this->repository,
            'versions' => array_map(static fn (VersionDTO $v): array => $v->toArray(), $this->versions),
            'maintainers' => array_map(static fn (MaintainerDTO $m): array => $m->toArray(), $this->maintainers),
            'homepage' => $this->homepage,
            'license' => $this->license,
            'downloads' => $this->downloads,
            'favers' => $this->favers,
        ];
    }
}
