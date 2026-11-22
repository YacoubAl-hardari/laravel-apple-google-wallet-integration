<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\GiftCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\OfferData;
use Yacoubalhaidari\AppleGoogleWallet\Support\RetailPassValidation;

class RetailPassDataTest extends PHPUnitTestCase
{
    public function test_gift_cards_preserve_exact_micros_and_explicit_timezone_offsets(): void
    {
        $card = GiftCardData::fromArray([
            ...$this->giftAttributes(),
            'balance_micros' => '9223372036854775807',
            'balance_updated_at' => '2026-11-22T10:30:00+03:00',
            'expires_at' => '2027-11-22T10:30:00Z',
        ]);

        $this->assertSame('9223372036854775807', $card->balanceMicros);
        $this->assertSame('2026-11-22T10:30:00+03:00', $card->balanceUpdatedAt->format(DATE_ATOM));
        $this->assertSame('+00:00', $card->expiresAt->format('P'));
    }

    #[DataProvider('invalidGiftAttributes')]
    public function test_gift_cards_reject_invalid_input_before_issuing_passes(array $attributes): void
    {
        $this->expectException(InvalidArgumentException::class);

        GiftCardData::fromArray([...$this->giftAttributes(), ...$attributes]);
    }

    public static function invalidGiftAttributes(): array
    {
        return [
            'missing card number' => [['card_number' => '  ']],
            'missing program identity' => [['program_id' => '']],
            'fractional micros' => [['balance_micros' => '1.5']],
            'floating point micros' => [['balance_micros' => 1000000.0]],
            'negative balance' => [['balance_micros' => '-1']],
            'int64 overflow' => [['balance_micros' => '9223372036854775808']],
            'exponential notation' => [['balance_micros' => '1e6']],
            'leading zeros' => [['balance_micros' => '001']],
            'invalid currency' => [['currency_code' => 'sar']],
            'legacy state alias' => [['state' => 'active']],
            'empty barcode' => [['barcode_value' => '']],
            'timestamp without timezone' => [['balance_updated_at' => '2026-11-22T10:30:00']],
            'invalid calendar date' => [['expires_at' => '2026-02-31T10:30:00Z']],
            'out of range latitude' => [['merchant_locations' => [['latitude' => 91, 'longitude' => 45]]]],
            'out of range longitude' => [['merchant_locations' => [['latitude' => 12, 'longitude' => -181]]]],
            'missing longitude' => [['merchant_locations' => [['latitude' => 12]]]],
            'too many locations' => [['merchant_locations' => array_fill(0, 11, ['latitude' => 12, 'longitude' => 45])]],
        ];
    }

    #[DataProvider('invalidOfferAttributes')]
    public function test_offers_reject_invalid_redemption_and_validity_data(array $attributes): void
    {
        $this->expectException(InvalidArgumentException::class);

        OfferData::fromArray([
            'id' => 'customer-1', 'program_id' => 'campaign-1', 'title' => 'خصم خاص', 'provider' => 'متجر',
            ...$attributes,
        ]);
    }

    public static function invalidOfferAttributes(): array
    {
        return [
            'empty title' => [['title' => '']],
            'unknown channel' => [['redemption_channel' => 'SOMEWHERE']],
            'reversed interval' => [['valid_from' => '2026-11-23T00:00:00Z', 'expires_at' => '2026-11-22T00:00:00Z']],
            'zero duration' => [['valid_from' => '2026-11-22T00:00:00Z', 'expires_at' => '2026-11-22T00:00:00Z']],
            'unsafe redemption link' => [['redeem_online_url' => 'javascript:alert(1)']],
            'cleartext redemption link' => [['redeem_online_url' => 'http://example.com/redeem']],
            'credentials in redemption URL' => [['redeem_online_url' => 'https://user:secret@example.com/redeem']],
            'empty barcode' => [['barcode_value' => '  ']],
        ];
    }

    public function test_zero_balances_and_boundary_coordinates_are_valid(): void
    {
        $card = GiftCardData::fromArray([
            ...$this->giftAttributes(),
            'balance_micros' => 0,
            'merchant_locations' => [['latitude' => -90, 'longitude' => 180]],
        ]);

        $this->assertSame('0', $card->balanceMicros);
        $this->assertSame([['latitude' => -90, 'longitude' => 180]], $card->merchantLocations);
    }

    public function test_nonfinite_locations_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RetailPassValidation::locations([['latitude' => NAN, 'longitude' => INF]]);
    }

    private function giftAttributes(): array
    {
        return [
            'id' => 'card-1', 'program_id' => 'merchant-1', 'program_name' => 'متجر',
            'card_number' => '001234', 'balance_micros' => '25500000', 'currency_code' => 'SAR',
        ];
    }
}
