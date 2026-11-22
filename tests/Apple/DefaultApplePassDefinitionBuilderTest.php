<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Tests\Apple;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Yacoubalhaidari\AppleGoogleWallet\Apple\DefaultApplePassDefinitionBuilder;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;
use Yacoubalhaidari\AppleGoogleWallet\Tests\TestCase;

class DefaultApplePassDefinitionBuilderTest extends TestCase
{
    public function test_maps_loyalty_validity_and_member_locations_without_changing_the_serial_number(): void
    {
        config(['apple-wallet.organization_contact' => 'support@example.com']);
        $program = new LoyaltyProgramData('program-1', 'Loyalty program', 10, 1, merchantLocations: [['latitude' => 12, 'longitude' => 45]]);
        $member = new MemberCardData('card-1', 'opaque-token', 2, 0, false, 'Member',
            state: 'INACTIVE',
            validFrom: '2026-12-01T10:00:00+03:00',
            validUntil: '2027-02-01T10:00:00+03:00',
            merchantLocations: [['latitude' => '13.0', 'longitude' => '46.0']],
        );

        $definition = (new DefaultApplePassDefinitionBuilder)->build($program, $member);

        $this->assertSame('card-1', $definition['serialNumber']);
        $this->assertTrue($definition['voided']);
        $this->assertSame('2027-02-01T10:00:00+03:00', $definition['expirationDate']);
        $this->assertSame([['latitude' => 13.0, 'longitude' => 46.0]], $definition['locations']);
        $this->assertSame('support@example.com', array_column($definition['storeCard']['backFields'], 'value', 'key')['contact']);
        $this->assertSame('2026-12-01T10:00:00+03:00', array_column($definition['storeCard']['backFields'], 'value', 'key')['valid_from']);
        $this->assertArrayNotHasKey('relevantDate', $definition);
    }

    public function test_allows_clearing_program_locations_and_deduplicates_configurable_front_fields(): void
    {
        config(['apple-wallet.fields' => [
            'visible' => ['rewards', 'remaining', 'member', 'status'],
            'secondary' => ['rewards', 'remaining', 'rewards'],
            'auxiliary' => ['member', 'status', 'member'],
        ]]);
        $program = new LoyaltyProgramData('program-1', 'Loyalty program', 10, 1, merchantLocations: [['latitude' => 12, 'longitude' => 45]]);
        $member = new MemberCardData('card-1', 'opaque-token', 2, 0, false, 'Member', merchantLocations: []);

        $definition = (new DefaultApplePassDefinitionBuilder)->build($program, $member);

        $this->assertArrayNotHasKey('locations', $definition);
        $this->assertSame(['rewards', 'remaining'], array_column($definition['storeCard']['secondaryFields'], 'key'));
        $this->assertSame(['member', 'status'], array_column($definition['storeCard']['auxiliaryFields'], 'key'));
        $this->assertArrayNotHasKey('expirationDate', $definition);
    }

    #[DataProvider('invalidValidityIntervals')]
    public function test_rejects_invalid_or_reversed_loyalty_validity_intervals(string $validFrom, string $validUntil): void
    {
        $program = new LoyaltyProgramData('program-1', 'Loyalty program', 10, 1);
        $member = new MemberCardData('card-1', 'opaque-token', 2, 0, false, 'Member', validFrom: $validFrom, validUntil: $validUntil);
        $this->expectException(InvalidArgumentException::class);

        (new DefaultApplePassDefinitionBuilder)->build($program, $member);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidValidityIntervals(): array
    {
        return [
            'malformed start' => ['not-a-date', '2027-01-01T00:00:00+03:00'],
            'end before start' => ['2027-02-01T00:00:00+03:00', '2027-01-01T00:00:00+03:00'],
            'equal instants in different timezones' => ['2027-01-01T03:00:00+03:00', '2027-01-01T00:00:00Z'],
        ];
    }
}
