<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Google;

use DateTimeImmutable;
use Google\Service\Walletobjects\GiftCardClass;
use Google\Service\Walletobjects\GiftCardObject;
use Google\Service\Walletobjects\OfferClass;
use Google\Service\Walletobjects\OfferObject;
use InvalidArgumentException;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\GiftCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\OfferData;

class DefaultGoogleRetailPayloadBuilder
{
    public function __construct(protected WalletImageUrlResolver $imageUrlResolver) {}

    public function buildGiftCardClass(GiftCardData $card): GiftCardClass
    {
        $payload = [
            'id' => $this->giftCardClassId($card),
            'issuerName' => config('google-wallet.issuer_name'),
            'merchantName' => $card->programName,
            'reviewStatus' => 'UNDER_REVIEW',
            'allowBarcodeRedemption' => true,
        ];

        if ($logo = $this->imageUrlResolver->resolve($card->logoUrl)) {
            $payload['programLogo'] = ['sourceUri' => ['uri' => $logo]];
        }

        return new GiftCardClass($payload);
    }

    public function buildGiftCardObject(GiftCardData $card): GiftCardObject
    {
        $payload = [
            'id' => $this->giftCardObjectId($card),
            'classId' => $this->giftCardClassId($card),
            'state' => $card->state,
            'cardNumber' => $card->cardNumber,
            'balance' => ['micros' => $card->balanceMicros, 'currencyCode' => $card->currencyCode],
            'barcode' => ['type' => 'QR_CODE', 'value' => $card->barcodeValue ?? $card->cardNumber],
            'merchantLocations' => $this->locations($card->merchantLocations),
        ];

        if ($card->pin !== null) {
            $payload['pin'] = $card->pin;
        }

        if ($card->expiresAt !== null) {
            $payload['validTimeInterval'] = $this->interval(null, $card->expiresAt);
        }

        if ($card->balanceUpdatedAt !== null) {
            $payload['balanceUpdateTime'] = ['date' => $card->balanceUpdatedAt->format(DATE_ATOM)];
        }

        return new GiftCardObject($payload);
    }

    public function buildOfferClass(OfferData $offer): OfferClass
    {
        $payload = [
            'id' => $this->offerClassId($offer),
            'issuerName' => config('google-wallet.issuer_name'),
            'title' => $offer->title,
            'provider' => $offer->provider,
            'redemptionChannel' => $offer->redemptionChannel,
            'reviewStatus' => 'UNDER_REVIEW',
        ];

        if ($offer->details !== null) {
            $payload['details'] = $offer->details;
        }

        if ($offer->terms !== null) {
            $payload['finePrint'] = $offer->terms;
        }

        if ($logo = $this->imageUrlResolver->resolve($offer->logoUrl)) {
            $payload['titleImage'] = ['sourceUri' => ['uri' => $logo]];
        }

        return new OfferClass($payload);
    }

    public function buildOfferObject(OfferData $offer): OfferObject
    {
        $payload = [
            'id' => $this->offerObjectId($offer),
            'classId' => $this->offerClassId($offer),
            'state' => $offer->state,
            'merchantLocations' => $this->locations($offer->merchantLocations),
        ];

        if ($offer->barcodeValue !== null) {
            $payload['barcode'] = ['type' => 'QR_CODE', 'value' => $offer->barcodeValue];
        }

        if ($offer->validFrom !== null || $offer->expiresAt !== null) {
            $payload['validTimeInterval'] = $this->interval($offer->validFrom, $offer->expiresAt);
        }

        if ($offer->redeemOnlineUrl !== null) {
            $payload['linksModuleData'] = ['uris' => [[
                'id' => 'redeem_online', 'uri' => $offer->redeemOnlineUrl, 'description' => $offer->title,
            ]]];
        }

        return new OfferObject($payload);
    }

    public function giftCardClassId(GiftCardData $card): string
    {
        return $this->identifier('gift_program', $card->programId);
    }

    public function giftCardObjectId(GiftCardData $card): string
    {
        return $this->identifier('gift_card', $card->programId."\0".$card->id);
    }

    public function offerClassId(OfferData $offer): string
    {
        return $this->identifier('offer_program', $offer->programId);
    }

    public function offerObjectId(OfferData $offer): string
    {
        return $this->identifier('offer', $offer->programId."\0".$offer->id);
    }

    protected function identifier(string $prefix, string $id): string
    {
        $issuer = (string) config('google-wallet.issuer_id');

        if (! preg_match('/^[0-9]+$/D', $issuer)) {
            throw new InvalidArgumentException('A numeric Google Wallet issuer ID is required.');
        }

        return $issuer.'.'.$prefix.'_'.hash('sha256', $id);
    }

    /**
     * @return array{start?: array{date: string}, end?: array{date: string}}
     */
    protected function interval(?DateTimeImmutable $start, ?DateTimeImmutable $end): array
    {
        $interval = [];

        if ($start !== null) {
            $interval['start'] = ['date' => $start->format(DATE_ATOM)];
        }

        if ($end !== null) {
            $interval['end'] = ['date' => $end->format(DATE_ATOM)];
        }

        return $interval;
    }

    /**
     * @param  list<array{latitude: float|int|string, longitude: float|int|string}>  $locations
     * @return list<array{latitude: float, longitude: float}>
     */
    protected function locations(array $locations): array
    {
        return array_map(fn (array $location): array => [
            'latitude' => (float) $location['latitude'],
            'longitude' => (float) $location['longitude'],
        ], $locations);
    }
}
