<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Tests;

use Google\Model;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;
use Yacoubalhaidari\AppleGoogleWallet\Google\DefaultGoogleLoyaltyPayloadBuilder;
use Yacoubalhaidari\AppleGoogleWallet\Google\WalletImageUrlResolver;

class GoogleLoyaltyPayloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'google-wallet.issuer_id' => '123456',
            'google-wallet.issuer_name' => 'Coffee Club',
        ]);
    }

    public function test_preserves_existing_identifiers_and_uses_current_google_enums(): void
    {
        $builder = $this->builder();

        $class = $this->payload($builder->buildClass($this->program()));
        $object = $this->payload($builder->buildObject($this->program(), $this->member()));

        $this->assertSame('123456.stamp_Coffee_Club', $class['id']);
        $this->assertSame('123456.member_42', $object['id']);
        $this->assertSame($class['id'], $object['classId']);
        $this->assertSame('ACTIVE', $object['state']);
        $this->assertSame('QR_CODE', $object['barcode']['type']);
        $this->assertArrayNotHasKey('merchantLocations', $class);
        $this->assertArrayNotHasKey('countryCode', $class);
        $this->assertArrayNotHasKey('linksModuleData', $class);
        $this->assertArrayNotHasKey('merchantLocations', $object);
        $this->assertArrayNotHasKey('linkedOfferIds', $object);
        $this->assertArrayNotHasKey('validTimeInterval', $object);
    }

    public function test_explicit_identifiers_keep_arabic_programs_distinct_and_survive_renaming(): void
    {
        $builder = $this->builder();
        $first = $this->program(['name' => 'ولاء', 'wallet_class_id' => '123456.program_10']);
        $second = $this->program(['id' => '11', 'name' => 'نقاط', 'wallet_class_id' => '123456.program_11']);
        $renamed = $this->program(['name' => 'اسمي الجديد', 'wallet_class_id' => '123456.program_10']);
        $member = $this->member(['wallet_object_id' => '123456.customer_42.program_10']);

        $firstPayload = $this->payload($builder->buildClass($first));
        $secondPayload = $this->payload($builder->buildClass($second));
        $renamedPayload = $this->payload($builder->buildClass($renamed));
        $object = $this->payload($builder->buildObject($renamed, $member));

        $this->assertSame('123456.program_10', $firstPayload['id']);
        $this->assertSame('123456.program_11', $secondPayload['id']);
        $this->assertSame($firstPayload['id'], $renamedPayload['id']);
        $this->assertSame('123456.customer_42.program_10', $object['id']);
        $this->assertSame($renamedPayload['id'], $object['classId']);
    }

    public function test_includes_configured_branch_locations_and_supports_explicit_removal(): void
    {
        $builder = $this->builder();
        $locations = [
            ['latitude' => 12.7855, 'longitude' => 45.0187],
            ['latitude' => -90, 'longitude' => 180],
        ];

        $class = $this->payload($builder->buildClass($this->program(['merchant_locations' => $locations])));
        $object = $this->payload($builder->buildObject($this->program(), $this->member(['merchant_locations' => $locations])));
        $clearedClass = $this->payload($builder->buildClass($this->program(['merchant_locations' => []])));
        $clearedObject = $this->payload($builder->buildObject($this->program(), $this->member(['merchant_locations' => []])));

        $this->assertSame($locations, $class['merchantLocations']);
        $this->assertSame($locations, $object['merchantLocations']);
        $this->assertSame([], $clearedClass['merchantLocations']);
        $this->assertSame([], $clearedObject['merchantLocations']);
    }

    public function test_sends_numeric_coordinates_when_locations_come_from_form_strings(): void
    {
        $locations = [['latitude' => '12.7855', 'longitude' => '45.0187', 'unexpected' => 'discarded']];

        $object = $this->payload($this->builder()->buildObject($this->program(), $this->member(['merchant_locations' => $locations])));

        $this->assertSame([['latitude' => 12.7855, 'longitude' => 45.0187]], $object['merchantLocations']);
    }

    public function test_maps_optional_country_and_bilingual_merchant_links_without_application_dependencies(): void
    {
        $links = [
            [
                'id' => 'website', 'uri' => 'https://merchant.example.com', 'description' => 'الموقع الإلكتروني',
                'localizedDescription' => [
                    'defaultValue' => ['language' => 'ar', 'value' => 'الموقع الإلكتروني'],
                    'translatedValues' => [['language' => 'en', 'value' => 'Website']],
                ],
            ],
            ['id' => 'directions', 'uri' => 'https://maps.google.com/?q=12.8,45', 'description' => 'Directions'],
        ];
        $program = $this->program(['country_code' => 'YE', 'merchant_links' => $links]);

        $class = $this->payload($this->builder()->buildClass($program));

        $this->assertSame('YE', $program->countryCode);
        $this->assertSame($links, $program->merchantLinks);
        $this->assertSame('YE', $class['countryCode']);
        $this->assertEquals(['uris' => $links], $class['linksModuleData']);
    }

    public function test_clears_explicitly_empty_merchant_links_and_leaves_null_options_unmanaged(): void
    {
        $builder = $this->builder();
        $cleared = $this->payload($builder->buildClass($this->program(['merchant_links' => []])));
        $unmanaged = $this->payload($builder->buildClass($this->program(['merchant_links' => null, 'country_code' => null])));

        $this->assertSame(['uris' => []], $cleared['linksModuleData']);
        $this->assertArrayNotHasKey('linksModuleData', $unmanaged);
        $this->assertArrayNotHasKey('countryCode', $unmanaged);
    }

    public function test_accepts_a_minimal_https_uri_and_a_default_translation_without_optional_fields(): void
    {
        $links = [
            ['uri' => 'https://merchant.example.com'],
            ['uri' => 'https://merchant.example.com/help', 'localizedDescription' => ['defaultValue' => ['language' => 'ar-YE', 'value' => 'المساعدة']]],
        ];

        $class = $this->payload($this->builder()->buildClass($this->program(['merchant_links' => $links])));

        $this->assertSame(['uris' => $links], $class['linksModuleData']);
    }

    #[DataProvider('invalidMerchantOptions')]
    public function test_rejects_invalid_country_and_link_data_before_resolving_remote_artwork(array $attributes): void
    {
        $resolver = $this->createMock(WalletImageUrlResolver::class);
        $resolver->expects($this->never())->method('resolve');
        $builder = new DefaultGoogleLoyaltyPayloadBuilder($resolver);
        $program = $this->program($attributes);
        $this->expectException(InvalidArgumentException::class);

        $builder->buildClass($program);
    }

    public static function invalidMerchantOptions(): array
    {
        return [
            'lowercase country' => [['country_code' => 'ye']],
            'three-letter country' => [['country_code' => 'YEM']],
            'empty country' => [['country_code' => '']],
            'nonalphabetic country' => [['country_code' => 'Y1']],
            'associative links' => [['merchant_links' => ['website' => ['uri' => 'https://merchant.example.com']]]],
            'missing URI' => [['merchant_links' => [['description' => 'Website']]]],
            'invalid URI' => [['merchant_links' => [['uri' => 'not-a-url']]]],
            'cleartext URI' => [['merchant_links' => [['uri' => 'http://merchant.example.com']]]],
            'javascript URI' => [['merchant_links' => [['uri' => 'javascript:alert(1)']]]],
            'credentials in URI' => [['merchant_links' => [['uri' => 'https://user:secret@merchant.example.com']]]],
            'numeric description' => [['merchant_links' => [['uri' => 'https://merchant.example.com', 'description' => 123]]]],
            'nonscalar ID' => [['merchant_links' => [['uri' => 'https://merchant.example.com', 'id' => []]]]],
            'unsupported field' => [['merchant_links' => [['uri' => 'https://merchant.example.com', 'accountId' => 'private']]]],
            'missing default translation' => [['merchant_links' => [['uri' => 'https://merchant.example.com', 'localizedDescription' => []]]]],
            'missing translation language' => [['merchant_links' => [['uri' => 'https://merchant.example.com', 'localizedDescription' => ['defaultValue' => ['value' => 'Website']]]]]],
            'numeric translation value' => [['merchant_links' => [['uri' => 'https://merchant.example.com', 'localizedDescription' => ['defaultValue' => ['language' => 'en', 'value' => 123]]]]]],
            'associative translations' => [['merchant_links' => [['uri' => 'https://merchant.example.com', 'localizedDescription' => ['defaultValue' => ['language' => 'en', 'value' => 'Website'], 'translatedValues' => ['ar' => ['language' => 'ar', 'value' => 'الموقع']]]]]]],
        ];
    }

    public function test_links_unique_offers_and_supports_explicit_removal_without_auto_attaching_passes(): void
    {
        $builder = $this->builder();
        $member = $this->member(['linked_offer_ids' => ['123456.offer_1', '123456.offer_1', '123456.offer_2']]);

        $object = $this->payload($builder->buildObject($this->program(), $member));
        $cleared = $this->payload($builder->buildObject($this->program(), $this->member(['linked_offer_ids' => []])));

        $this->assertSame(['123456.offer_1', '123456.offer_2'], $object['linkedOfferIds']);
        $this->assertSame([], $cleared['linkedOfferIds']);
        $this->assertArrayNotHasKey('linkedObjectIds', $object);
    }

    public function test_sets_member_state_and_an_absolute_validity_interval(): void
    {
        $member = $this->member([
            'state' => 'INACTIVE',
            'valid_from' => '2026-11-22T09:00:00+03:00',
            'valid_until' => '2026-12-22T09:00:00+03:00',
        ]);

        $object = $this->payload($this->builder()->buildObject($this->program(), $member));

        $this->assertSame('INACTIVE', $object['state']);
        $this->assertSame([
            'start' => ['date' => '2026-11-22T09:00:00+03:00'],
            'end' => ['date' => '2026-12-22T09:00:00+03:00'],
        ], $object['validTimeInterval']);
    }

    public function test_accepts_an_expiry_without_a_start_date(): void
    {
        $member = $this->member(['valid_until' => '2026-12-22T09:00:00+03:00']);

        $object = $this->payload($this->builder()->buildObject($this->program(), $member));

        $this->assertSame(['end' => ['date' => '2026-12-22T09:00:00+03:00']], $object['validTimeInterval']);
    }

    #[DataProvider('invalidWalletIdentifiers')]
    public function test_rejects_cross_issuer_or_malformed_identifiers(string $field, mixed $identifier): void
    {
        $program = $this->program($field === 'wallet_class_id' ? [$field => $identifier] : []);
        $member = $this->member($field === 'wallet_class_id' ? [] : [$field => $identifier]);
        $this->expectException(InvalidArgumentException::class);

        $this->builder()->buildObject($program, $member);
    }

    public static function invalidWalletIdentifiers(): array
    {
        return [
            'class from another issuer' => ['wallet_class_id', '987654.program_10'],
            'object from another issuer' => ['wallet_object_id', '987654.member_42'],
            'offer from another issuer' => ['linked_offer_ids', ['987654.offer_1']],
            'offer without a full identifier' => ['linked_offer_ids', ['offer_1']],
            'path in an object identifier' => ['wallet_object_id', '123456.member/42'],
            'empty explicit identifier' => ['wallet_object_id', ''],
            'non-string offer identifier' => ['linked_offer_ids', [42]],
        ];
    }

    #[DataProvider('invalidMemberOptions')]
    public function test_rejects_invalid_member_state_dates_and_locations(array $attributes): void
    {
        $member = $this->member($attributes);
        $this->expectException(InvalidArgumentException::class);

        $this->builder()->buildObject($this->program(), $member);
    }

    public static function invalidMemberOptions(): array
    {
        return [
            'unsupported state' => [['state' => 'UNKNOWN']],
            'expiry before start' => [[
                'valid_from' => '2026-12-22T09:00:00+03:00',
                'valid_until' => '2026-11-22T09:00:00+03:00',
            ]],
            'expiry equals start' => [[
                'valid_from' => '2026-11-22T09:00:00+03:00',
                'valid_until' => '2026-11-22T09:00:00+03:00',
            ]],
            'missing timezone' => [['valid_until' => '2026-11-22T09:00:00']],
            'impossible date' => [['valid_until' => '2026-02-30T09:00:00+03:00']],
            'invalid longitude' => [['merchant_locations' => [['latitude' => 12, 'longitude' => 181]]]],
        ];
    }

    public function test_rejects_more_than_ten_locations_on_a_program(): void
    {
        $locations = array_fill(0, 11, ['latitude' => 12.7855, 'longitude' => 45.0187]);
        $program = $this->program(['merchant_locations' => $locations]);
        $this->expectException(InvalidArgumentException::class);

        $this->builder()->buildClass($program);
    }

    private function builder(): DefaultGoogleLoyaltyPayloadBuilder
    {
        $resolver = $this->createStub(WalletImageUrlResolver::class);
        $resolver->method('resolve')->willReturn('https://assets.example.com/logo.png');

        return new DefaultGoogleLoyaltyPayloadBuilder($resolver);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function program(array $attributes = []): LoyaltyProgramData
    {
        return LoyaltyProgramData::fromArray([
            'id' => '10',
            'name' => 'Coffee Club',
            'required_stamps' => 5,
            'reward_count' => 1,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function member(array $attributes = []): MemberCardData
    {
        return MemberCardData::fromArray([
            'id' => 42,
            'qr_code' => 'member-42-qr-code',
            'stamps_progress' => 2,
            'rewards_earned' => 0,
            'is_completed' => false,
            'member_name' => 'Ali',
            ...$attributes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Model $model): array
    {
        return json_decode(json_encode($model->toSimpleObject(), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }
}
