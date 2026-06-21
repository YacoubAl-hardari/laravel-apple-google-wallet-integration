<?php

namespace Yacoubalhaidari\AppleGoogleWallet\DTOs;

final readonly class MemberCardData
{
    public function __construct(
        public string|int $id,
        public string $qrCode,
        public int $stampsProgress,
        public int $rewardsEarned,
        public bool $isCompleted,
        public string $memberName,
    ) {
    }

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
        );
    }
}
