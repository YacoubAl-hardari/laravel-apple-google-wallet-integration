<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Tests\Apple;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Yacoubalhaidari\AppleGoogleWallet\Apple\DefaultAppleRetailPassDefinitionBuilder;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\GiftCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\OfferData;
use Yacoubalhaidari\AppleGoogleWallet\Tests\TestCase;

class DefaultAppleRetailPassDefinitionBuilderTest extends TestCase
{
    #[DataProvider('giftCardBalances')]
    public function test_displays_gift_card_amounts_without_losing_micros(string $micros, string $display): void
    {
        $card = new GiftCardData('card-1', 'program-1', 'Gift program', '001234', $micros, 'SAR');

        $definition = (new DefaultAppleRetailPassDefinitionBuilder)->buildGiftCard($card);

        $this->assertSame($display, $definition['storeCard']['primaryFields'][0]['value']);
        $this->assertSame('001234', $definition['storeCard']['secondaryFields'][0]['value']);
        $this->assertSame('001234', $definition['barcodes'][0]['message']);
        $this->assertArrayNotHasKey('coupon', $definition);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function giftCardBalances(): array
    {
        return [
            'zero' => ['0', '0 SAR'],
            'single micro' => ['1', '0.000001 SAR'],
            'fraction' => ['25500000', '25.5 SAR'],
            'int64 maximum' => ['9223372036854775807', '9223372036854.775807 SAR'],
        ];
    }

    public function test_preserves_gift_expiry_status_locations_and_optional_pin(): void
    {
        config(['apple-wallet.organization_contact' => 'support@example.com']);
        $card = new GiftCardData(
            id: 'card-1',
            programId: 'program-1',
            programName: 'Gift program',
            cardNumber: '001234',
            balanceMicros: '25500000',
            currencyCode: 'SAR',
            barcodeValue: 'opaque-redemption-token',
            pin: '0091',
            expiresAt: new DateTimeImmutable('2027-02-01T10:00:00+03:00'),
            balanceUpdatedAt: new DateTimeImmutable('2026-11-22T10:00:00+03:00'),
            state: 'INACTIVE',
            merchantLocations: [['latitude' => '12.7855', 'longitude' => '45.0187']],
        );

        $definition = (new DefaultAppleRetailPassDefinitionBuilder)->buildGiftCard($card);

        $this->assertSame('2027-02-01T10:00:00+03:00', $definition['expirationDate']);
        $this->assertTrue($definition['voided']);
        $this->assertSame([['latitude' => 12.7855, 'longitude' => 45.0187]], $definition['locations']);
        $this->assertSame('opaque-redemption-token', $definition['barcodes'][0]['message']);
        $backFields = array_column($definition['storeCard']['backFields'], 'value', 'key');
        $this->assertSame('0091', $backFields['pin']);
        $this->assertSame('2026-11-22T10:00:00+03:00', $backFields['balance_updated']);
        $this->assertSame('support@example.com', $backFields['contact']);
        $allFields = array_merge(
            $definition['storeCard']['primaryFields'],
            $definition['storeCard']['secondaryFields'],
            $definition['storeCard']['auxiliaryFields'],
            $definition['storeCard']['backFields'],
        );
        $this->assertSame(array_column($allFields, 'key'), array_values(array_unique(array_column($allFields, 'key'))));
    }

    public function test_creates_coupon_offers_with_terms_dates_online_redemption_and_no_invented_barcode(): void
    {
        $offer = new OfferData(
            id: 'offer-1',
            programId: 'program-1',
            title: 'خصم ٢٥٪',
            provider: 'Example merchant',
            redemptionChannel: 'ONLINE',
            validFrom: new DateTimeImmutable('2026-12-01T00:00:00+03:00'),
            expiresAt: new DateTimeImmutable('2026-12-31T23:59:59+03:00'),
            details: 'Member discount',
            terms: 'One redemption per customer.',
            redeemOnlineUrl: 'https://example.com/redeem/opaque-token',
        );

        $definition = (new DefaultAppleRetailPassDefinitionBuilder)->buildOffer($offer);

        $this->assertSame('خصم ٢٥٪', $definition['coupon']['primaryFields'][0]['value']);
        $this->assertSame('2026-12-31T23:59:59+03:00', $definition['expirationDate']);
        $this->assertFalse($definition['voided']);
        $backFields = array_column($definition['coupon']['backFields'], 'value', 'key');
        $this->assertSame('One redemption per customer.', $backFields['terms']);
        $this->assertSame('https://example.com/redeem/opaque-token', $backFields['redeem_online']);
        $this->assertSame('2026-12-01T00:00:00+03:00', $backFields['valid_from']);
        $this->assertArrayNotHasKey('barcodes', $definition);
        $this->assertArrayNotHasKey('storeCard', $definition);
        $this->assertArrayNotHasKey('webServiceURL', $definition);
    }

    public function test_namespaces_serials_between_programs_and_pass_types(): void
    {
        $builder = new DefaultAppleRetailPassDefinitionBuilder;
        $firstGift = $builder->buildGiftCard(new GiftCardData('shared-id', 'program-a', 'Gift program', '1', '0', 'SAR'));
        $secondGift = $builder->buildGiftCard(new GiftCardData('shared-id', 'program-b', 'Gift program', '1', '0', 'SAR'));
        $offer = $builder->buildOffer(new OfferData('shared-id', 'program-a', 'Offer', 'Merchant'));

        $this->assertNotSame($firstGift['serialNumber'], $secondGift['serialNumber']);
        $this->assertNotSame($firstGift['serialNumber'], $offer['serialNumber']);
        $this->assertSame($firstGift['serialNumber'], $builder->buildGiftCard(new GiftCardData('shared-id', 'program-a', 'Renamed program', '1', '1000000', 'SAR'))['serialNumber']);
    }
}
