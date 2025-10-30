<?php

declare(strict_types=1);

namespace Akira\Packagist\DTOs;

/**
 * @psalm-immutable
 */
final readonly class VersionDTO
{
    /**
     * @param  array<string, mixed>  $keywords
     * @param  array<string, mixed>  $authors
     */
    public function __construct(
        public string $version,
        public string $name,
        public string $description,
        public string $require,
        public array $keywords = [],
        public ?string $homepage = null,
        public ?string $license = null,
        public array $authors = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Handle license as string or array
        $license = null;
        if (isset($data['license'])) {
            $license = is_array($data['license']) ? implode(', ', $data['license']) : (string) $data['license'];
        }

        return new self(
            version: (string) ($data['version'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            require: (string) (json_encode($data['require'] ?? [])),
            keywords: (array) ($data['keywords'] ?? []),
            homepage: isset($data['homepage']) ? (string) $data['homepage'] : null,
            license: $license,
            authors: (array) ($data['authors'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'name' => $this->name,
            'description' => $this->description,
            'require' => $this->require,
            'keywords' => $this->keywords,
            'homepage' => $this->homepage,
            'license' => $this->license,
            'authors' => $this->authors,
        ];
    }
}
