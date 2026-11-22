<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Tests;

use Google\Model;
use InvalidArgumentException;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\GiftCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\OfferData;
use Yacoubalhaidari\AppleGoogleWallet\Google\DefaultGoogleRetailPayloadBuilder;
use Yacoubalhaidari\AppleGoogleWallet\Google\WalletImageUrlResolver;

class GoogleRetailPayloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['google-wallet.issuer_id' => '12345', 'google-wallet.issuer_name' => 'Merchant']);
    }

    public function test_gift_objects_use_native_money_and_offset_aware_validity(): void
    {
        $builder = $this->builder();
        $card = GiftCardData::fromArray([
            ...$this->giftAttributes(), 'pin' => '1234',
            'balance_micros' => '9223372036854775807',
            'balance_updated_at' => '2026-11-22T10:30:00+03:00',
            'expires_at' => '2027-11-22T10:30:00Z',
            'merchant_locations' => [['latitude' => '12.8', 'longitude' => '45.03']],
        ]);

        $payload = $this->payload($builder->buildGiftCardObject($card));

        $this->assertSame(['currencyCode' => 'SAR', 'micros' => '9223372036854775807'], $payload['balance']);
        $this->assertSame('001234', $payload['cardNumber']);
        $this->assertSame('1234', $payload['pin']);
        $this->assertSame('ACTIVE', $payload['state']);
        $this->assertSame('QR_CODE', $payload['barcode']['type']);
        $this->assertSame('001234', $payload['barcode']['value']);
        $this->assertSame('2026-11-22T10:30:00+03:00', $payload['balanceUpdateTime']['date']);
        $this->assertSame('2027-11-22T10:30:00+00:00', $payload['validTimeInterval']['end']['date']);
        $this->assertSame([['latitude' => 12.8, 'longitude' => 45.03]], $payload['merchantLocations']);
    }

    public function test_offer_payloads_use_native_redemption_fields_and_online_links(): void
    {
        $builder = $this->builder();
        $offer = OfferData::fromArray([
            'id' => 'member-1', 'program_id' => 'campaign-1', 'title' => 'خصم 20%', 'provider' => 'متجر',
            'redemption_channel' => 'BOTH', 'barcode_value' => 'secure-redemption-token',
            'valid_from' => '2026-11-22T10:00:00+03:00', 'expires_at' => '2026-11-30T23:59:00+03:00',
            'details' => 'تفاصيل العرض', 'terms' => 'الشروط', 'logo_url' => 'https://example.com/logo.png',
            'redeem_online_url' => 'https://example.com/redeem', 'state' => 'COMPLETED',
        ]);

        $class = $this->payload($builder->buildOfferClass($offer));
        $object = $this->payload($builder->buildOfferObject($offer));

        $this->assertSame('خصم 20%', $class['title']);
        $this->assertSame('متجر', $class['provider']);
        $this->assertSame('BOTH', $class['redemptionChannel']);
        $this->assertSame('الشروط', $class['finePrint']);
        $this->assertSame('تفاصيل العرض', $class['details']);
        $this->assertSame('https://example.com/logo.png', $class['titleImage']['sourceUri']['uri']);
        $this->assertSame('COMPLETED', $object['state']);
        $this->assertSame('secure-redemption-token', $object['barcode']['value']);
        $this->assertSame('2026-11-30T23:59:00+03:00', $object['validTimeInterval']['end']['date']);
        $this->assertSame('https://example.com/redeem', $object['linksModuleData']['uris'][0]['uri']);
    }

    public function test_gift_identity_survives_merchant_rename_and_namespaces_reused_card_ids(): void
    {
        $builder = $this->builder();
        $first = GiftCardData::fromArray($this->giftAttributes());
        $renamed = GiftCardData::fromArray([...$this->giftAttributes(), 'program_name' => 'New merchant name']);
        $otherMerchant = GiftCardData::fromArray([...$this->giftAttributes(), 'program_id' => 'merchant-2']);

        $this->assertSame($builder->giftCardClassId($first), $builder->giftCardClassId($renamed));
        $this->assertSame($builder->giftCardObjectId($first), $builder->giftCardObjectId($renamed));
        $this->assertNotSame($builder->giftCardClassId($first), $builder->giftCardClassId($otherMerchant));
        $this->assertNotSame($builder->giftCardObjectId($first), $builder->giftCardObjectId($otherMerchant));
    }

    public function test_arabic_identifiers_remain_distinct_without_exposing_card_numbers(): void
    {
        $builder = $this->builder();
        $first = GiftCardData::fromArray([...$this->giftAttributes(), 'program_id' => 'متجر أول']);
        $second = GiftCardData::fromArray([...$this->giftAttributes(), 'program_id' => 'متجر ثان']);

        $this->assertNotSame($builder->giftCardClassId($first), $builder->giftCardClassId($second));
        $this->assertMatchesRegularExpression('/^12345\.gift_card_[a-f0-9]{64}$/D', $builder->giftCardObjectId($first));
        $this->assertStringNotContainsString('001234', $builder->giftCardObjectId($first));
    }

    public function test_unconfigured_issuer_is_rejected_before_building_an_identifier(): void
    {
        config(['google-wallet.issuer_id' => 'not-an-issuer']);

        $this->expectException(InvalidArgumentException::class);

        $this->builder()->buildGiftCardObject(GiftCardData::fromArray($this->giftAttributes()));
    }

    private function builder(): DefaultGoogleRetailPayloadBuilder
    {
        return new DefaultGoogleRetailPayloadBuilder(new class extends WalletImageUrlResolver
        {
            public function resolve(?string $url): ?string
            {
                return $url;
            }
        });
    }

    private function payload(Model $model): array
    {
        return json_decode(json_encode($model->toSimpleObject(), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }

    private function giftAttributes(): array
    {
        return [
            'id' => 'card-1', 'program_id' => 'merchant-1', 'program_name' => 'متجر',
            'card_number' => '001234', 'balance_micros' => '25500000', 'currency_code' => 'SAR',
        ];
    }
}
