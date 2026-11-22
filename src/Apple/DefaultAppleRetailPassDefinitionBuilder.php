<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Apple;

use DateTimeImmutable;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\GiftCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\OfferData;
use Yacoubalhaidari\AppleGoogleWallet\Support\AppleColorNormalizer;

class DefaultAppleRetailPassDefinitionBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function buildGiftCard(GiftCardData $card): array
    {
        $definition = $this->baseDefinition('gift_card', $card->programId, $card->id, $card->programName, $card->state, $card->expiresAt, $card->merchantLocations);
        $backFields = [
            ['key' => 'program', 'label' => wallet_trans('program'), 'value' => $card->programName],
        ];

        if ($card->pin !== null && $card->pin !== '') {
            $backFields[] = ['key' => 'pin', 'label' => wallet_trans('pin'), 'value' => $card->pin];
        }

        if ($card->balanceUpdatedAt !== null) {
            $backFields[] = $this->dateField('balance_updated', $card->balanceUpdatedAt);
        }

        $definition['storeCard'] = [
            'primaryFields' => [[
                'key' => 'balance',
                'label' => wallet_trans('balance'),
                'value' => $this->decimalAmount($card->balanceMicros).' '.$card->currencyCode,
            ]],
            'secondaryFields' => [[
                'key' => 'card_number',
                'label' => wallet_trans('card_number'),
                'value' => $card->cardNumber,
            ]],
            'auxiliaryFields' => $card->expiresAt === null ? [] : [$this->dateField('expires', $card->expiresAt)],
            'backFields' => $this->withContact($backFields),
        ];

        return $this->withBarcode($definition, $card->barcodeValue ?? $card->cardNumber);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildOffer(OfferData $offer): array
    {
        $definition = $this->baseDefinition('offer', $offer->programId, $offer->id, $offer->title, $offer->state, $offer->expiresAt, $offer->merchantLocations);
        $backFields = [['key' => 'provider', 'label' => wallet_trans('provider'), 'value' => $offer->provider]];

        foreach (['details' => 'offer_details', 'terms' => 'terms', 'redeemOnlineUrl' => 'redeem_online'] as $property => $key) {
            if ($offer->{$property} !== null && $offer->{$property} !== '') {
                $backFields[] = ['key' => $key, 'label' => wallet_trans($key), 'value' => $offer->{$property}];
            }
        }

        if ($offer->validFrom !== null) {
            $backFields[] = $this->dateField('valid_from', $offer->validFrom);
        }

        $definition['coupon'] = [
            'primaryFields' => [['key' => 'offer', 'label' => $offer->provider, 'value' => $offer->title]],
            'secondaryFields' => $offer->expiresAt === null ? [] : [$this->dateField('expires', $offer->expiresAt)],
            'auxiliaryFields' => [],
            'backFields' => $this->withContact($backFields),
        ];

        return $this->withBarcode($definition, $offer->barcodeValue);
    }

    /**
     * @param  list<array{latitude: float|int|string, longitude: float|int|string}>  $locations
     * @return array<string, mixed>
     */
    protected function baseDefinition(string $type, string $programId, string $id, string $description, string $state, ?DateTimeImmutable $expiresAt, array $locations): array
    {
        $definition = [
            'description' => $description,
            'formatVersion' => 1,
            'organizationName' => (string) config('apple-wallet.organization_name'),
            'passTypeIdentifier' => (string) config('apple-wallet.pass_type_identifier'),
            'serialNumber' => $type.'_'.hash('sha256', $programId."\0".$id),
            'teamIdentifier' => (string) config('apple-wallet.team_identifier'),
            'foregroundColor' => AppleColorNormalizer::normalize(config('apple-wallet.foreground_color'), 'rgb(255, 255, 255)'),
            'backgroundColor' => AppleColorNormalizer::normalize(config('apple-wallet.background_color'), 'rgb(139, 94, 60)'),
            'labelColor' => AppleColorNormalizer::normalize(config('apple-wallet.label_color'), 'rgb(255, 255, 255)'),
            'voided' => $state !== 'ACTIVE',
        ];

        if ($expiresAt !== null) {
            $definition['expirationDate'] = $expiresAt->format(DATE_ATOM);
        }

        if ($locations !== []) {
            $definition['locations'] = array_map(fn (array $location): array => [
                'latitude' => (float) $location['latitude'],
                'longitude' => (float) $location['longitude'],
            ], $locations);
        }

        return $definition;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    protected function withBarcode(array $definition, ?string $value): array
    {
        if ($value !== null && $value !== '') {
            $barcode = [
                'message' => $value,
                'format' => 'PKBarcodeFormatQR',
                'messageEncoding' => 'utf-8',
            ];
            $definition['barcode'] = $barcode;
            $definition['barcodes'] = [$barcode];
        }

        return $definition;
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return list<array<string, mixed>>
     */
    protected function withContact(array $fields): array
    {
        $contact = config('apple-wallet.organization_contact');

        if (is_string($contact) && trim($contact) !== '') {
            $fields[] = ['key' => 'contact', 'label' => wallet_trans('contact'), 'value' => $contact];
        }

        return $fields;
    }

    /**
     * @return array{key: string, label: string, value: string, dateStyle: string, timeStyle: string}
     */
    protected function dateField(string $key, DateTimeImmutable $date): array
    {
        return [
            'key' => $key,
            'label' => wallet_trans($key),
            'value' => $date->format(DATE_ATOM),
            'dateStyle' => 'PKDateStyleMedium',
            'timeStyle' => 'PKDateStyleShort',
        ];
    }

    protected function decimalAmount(string $micros): string
    {
        $padded = str_pad($micros, 7, '0', STR_PAD_LEFT);
        $whole = substr($padded, 0, -6);
        $fraction = rtrim(substr($padded, -6), '0');

        return $whole.($fraction === '' ? '' : '.'.$fraction);
    }
}
