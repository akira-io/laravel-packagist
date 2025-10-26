<?php

declare(strict_types=1);

namespace Akira\Packagist\DTOs;

/**
 * @psalm-immutable
 */
final readonly class MaintainerDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $homepage = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            homepage: isset($data['homepage']) ? (string) $data['homepage'] : null,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'homepage' => $this->homepage,
        ];
    }
}