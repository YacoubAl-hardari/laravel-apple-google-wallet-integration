<?php

namespace Yacoubalhaidari\AppleGoogleWallet\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;
use Yacoubalhaidari\AppleGoogleWallet\Support\RetailPassValidation;

final readonly class OfferData
{
    /**
     * @param  list<array{latitude: float|int|string, longitude: float|int|string}>  $merchantLocations
     */
    public function __construct(
        public string $id,
        public string $programId,
        public string $title,
        public string $provider,
        public string $redemptionChannel = 'BOTH',
        public ?string $barcodeValue = null,
        public ?DateTimeImmutable $validFrom = null,
        public ?DateTimeImmutable $expiresAt = null,
        public ?string $details = null,
        public ?string $terms = null,
        public ?string $logoUrl = null,
        public string $state = 'ACTIVE',
        public array $merchantLocations = [],
        public ?string $redeemOnlineUrl = null,
    ) {
        foreach (['id', 'programId', 'title', 'provider'] as $field) {
            RetailPassValidation::required($this->{$field}, $field);
        }

        RetailPassValidation::state($state);
        RetailPassValidation::locations($merchantLocations);

        if ($barcodeValue !== null) {
            RetailPassValidation::required($barcodeValue, 'barcodeValue');
        }

        if (! in_array($redemptionChannel, ['INSTORE', 'ONLINE', 'BOTH', 'TEMPORARY_PRICE_REDUCTION'], true)) {
            throw new InvalidArgumentException('Unsupported offer redemption channel.');
        }

        if ($validFrom !== null && $expiresAt !== null && $expiresAt <= $validFrom) {
            throw new InvalidArgumentException('An offer must expire after its start time.');
        }

        if ($redeemOnlineUrl !== null && (! filter_var($redeemOnlineUrl, FILTER_VALIDATE_URL)
            || strtolower((string) parse_url($redeemOnlineUrl, PHP_URL_SCHEME)) !== 'https'
            || parse_url($redeemOnlineUrl, PHP_URL_USER) !== null
            || parse_url($redeemOnlineUrl, PHP_URL_PASS) !== null)) {
            throw new InvalidArgumentException('Online redemption requires a valid HTTPS URL without credentials.');
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            id: (string) ($attributes['id'] ?? ''),
            programId: (string) ($attributes['program_id'] ?? ''),
            title: (string) ($attributes['title'] ?? ''),
            provider: (string) ($attributes['provider'] ?? ''),
            redemptionChannel: (string) ($attributes['redemption_channel'] ?? 'BOTH'),
            barcodeValue: isset($attributes['barcode_value']) ? (string) $attributes['barcode_value'] : null,
            validFrom: RetailPassValidation::date($attributes['valid_from'] ?? null, 'valid_from'),
            expiresAt: RetailPassValidation::date($attributes['expires_at'] ?? null, 'expires_at'),
            details: isset($attributes['details']) ? (string) $attributes['details'] : null,
            terms: isset($attributes['terms']) ? (string) $attributes['terms'] : null,
            logoUrl: isset($attributes['logo_url']) ? (string) $attributes['logo_url'] : null,
            state: (string) ($attributes['state'] ?? 'ACTIVE'),
            merchantLocations: $attributes['merchant_locations'] ?? [],
            redeemOnlineUrl: isset($attributes['redeem_online_url']) ? (string) $attributes['redeem_online_url'] : null,
        );
    }
}
