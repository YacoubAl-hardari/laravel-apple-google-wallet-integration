<?php

namespace Yacoubalhaidari\AppleGoogleWallet\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;
use Yacoubalhaidari\AppleGoogleWallet\Support\RetailPassValidation;

final readonly class GiftCardData
{
    /**
     * @param  list<array{latitude: float|int|string, longitude: float|int|string}>  $merchantLocations
     */
    public function __construct(
        public string $id,
        public string $programId,
        public string $programName,
        public string $cardNumber,
        public string $balanceMicros,
        public string $currencyCode,
        public ?string $barcodeValue = null,
        public ?string $pin = null,
        public ?DateTimeImmutable $expiresAt = null,
        public ?DateTimeImmutable $balanceUpdatedAt = null,
        public string $state = 'ACTIVE',
        public ?string $logoUrl = null,
        public array $merchantLocations = [],
    ) {
        foreach (['id', 'programId', 'programName', 'cardNumber'] as $field) {
            RetailPassValidation::required($this->{$field}, $field);
        }

        RetailPassValidation::micros($balanceMicros);
        RetailPassValidation::state($state);
        RetailPassValidation::locations($merchantLocations);

        if ($barcodeValue !== null) {
            RetailPassValidation::required($barcodeValue, 'barcodeValue');
        }

        if (! preg_match('/^[A-Z]{3}$/D', $currencyCode)) {
            throw new InvalidArgumentException('Gift cards require a three-letter uppercase currency code.');
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
            programName: (string) ($attributes['program_name'] ?? ''),
            cardNumber: (string) ($attributes['card_number'] ?? ''),
            balanceMicros: RetailPassValidation::micros($attributes['balance_micros'] ?? null),
            currencyCode: (string) ($attributes['currency_code'] ?? ''),
            barcodeValue: isset($attributes['barcode_value']) ? (string) $attributes['barcode_value'] : null,
            pin: isset($attributes['pin']) ? (string) $attributes['pin'] : null,
            expiresAt: RetailPassValidation::date($attributes['expires_at'] ?? null, 'expires_at'),
            balanceUpdatedAt: RetailPassValidation::date($attributes['balance_updated_at'] ?? null, 'balance_updated_at'),
            state: (string) ($attributes['state'] ?? 'ACTIVE'),
            logoUrl: isset($attributes['logo_url']) ? (string) $attributes['logo_url'] : null,
            merchantLocations: $attributes['merchant_locations'] ?? [],
        );
    }
}
