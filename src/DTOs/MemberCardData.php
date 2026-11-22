<?php

namespace Yacoubalhaidari\AppleGoogleWallet\DTOs;

use DateTimeInterface;

final readonly class MemberCardData
{
    /**
     * @param  list<string>|null  $linkedOfferIds
     * @param  list<array{latitude: int|float|string, longitude: int|float|string}>|null  $merchantLocations
     */
    public function __construct(
        public string|int $id,
        public string $qrCode,
        public int $stampsProgress,
        public int $rewardsEarned,
        public bool $isCompleted,
        public string $memberName,
        public ?string $walletObjectId = null,
        public ?array $linkedOfferIds = null,
        public string $state = 'ACTIVE',
        public DateTimeInterface|string|null $validFrom = null,
        public DateTimeInterface|string|null $validUntil = null,
        public ?array $merchantLocations = null,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            id: $attributes['id'] ?? '',
            qrCode: (string) ($attributes['qr_code'] ?? ''),
            stampsProgress: max(0, (int) ($attributes['stamps_progress'] ?? $attributes['washes_progress'] ?? 0)),
            rewardsEarned: max(0, (int) ($attributes['rewards_earned'] ?? 0)),
            isCompleted: (bool) ($attributes['is_completed'] ?? false),
            memberName: (string) ($attributes['member_name'] ?? ''),
            walletObjectId: isset($attributes['wallet_object_id']) ? (string) $attributes['wallet_object_id'] : null,
            linkedOfferIds: $attributes['linked_offer_ids'] ?? null,
            state: (string) ($attributes['state'] ?? 'ACTIVE'),
            validFrom: $attributes['valid_from'] ?? null,
            validUntil: $attributes['valid_until'] ?? null,
            merchantLocations: $attributes['merchant_locations'] ?? null,
        );
    }
}
