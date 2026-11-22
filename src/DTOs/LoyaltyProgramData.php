<?php

namespace Yacoubalhaidari\AppleGoogleWallet\DTOs;

final readonly class LoyaltyProgramData
{
    /**
     * @param  list<array{latitude: int|float|string, longitude: int|float|string}>|null  $merchantLocations
     * @param  list<array{uri: string, id?: string, description?: string, localizedDescription?: array{defaultValue: array{language: string, value: string}, translatedValues?: list<array{language: string, value: string}>}}>|null  $merchantLinks
     */
    public function __construct(
        public string $id,
        public string $name,
        public int $requiredStamps,
        public int $rewardCount,
        public ?string $logoPath = null,
        public ?string $imageUrl = null,
        public ?string $walletClassId = null,
        public ?array $merchantLocations = null,
        public ?string $countryCode = null,
        public ?array $merchantLinks = null,
    ) {}

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
            walletClassId: isset($attributes['wallet_class_id']) ? (string) $attributes['wallet_class_id'] : null,
            merchantLocations: $attributes['merchant_locations'] ?? null,
            countryCode: isset($attributes['country_code']) ? (string) $attributes['country_code'] : null,
            merchantLinks: $attributes['merchant_links'] ?? null,
        );
    }
}
