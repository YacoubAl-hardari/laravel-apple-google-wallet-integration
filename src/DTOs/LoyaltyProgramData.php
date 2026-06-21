<?php

namespace Yacoubalhaidari\AppleGoogleWallet\DTOs;

final readonly class LoyaltyProgramData
{
    public function __construct(
        public string $id,
        public string $name,
        public int $requiredStamps,
        public int $rewardCount,
        public ?string $logoPath = null,
        public ?string $imageUrl = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            id: (string) ($attributes['id'] ?? ''),
            name: (string) ($attributes['name'] ?? ''),
            requiredStamps: max(1, (int) ($attributes['required_stamps'] ?? $attributes['required_washes'] ?? 1)),
            rewardCount: max(1, (int) ($attributes['reward_count'] ?? $attributes['reward_free_washes'] ?? 1)),
            logoPath: isset($attributes['logo_path']) ? (string) $attributes['logo_path'] : null,
            imageUrl: isset($attributes['image_url']) ? (string) $attributes['image_url'] : ($attributes['image'] ?? null),
        );
    }
}
