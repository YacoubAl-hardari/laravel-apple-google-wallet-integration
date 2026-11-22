<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Tests\Apple;

use Illuminate\Support\Facades\Storage;
use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleStampStripGenerator;
use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleWalletService;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\GiftCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\OfferData;
use Yacoubalhaidari\AppleGoogleWallet\Tests\TestCase;

class AppleWalletServiceTest extends TestCase
{
    use CreatesSigningCertificates;

    public function test_creates_signed_gift_cards_and_offers_through_the_registered_service(): void
    {
        $this->configureAppleSigning();
        $wallet = $this->app->make(AppleWalletService::class);
        $gift = new GiftCardData('card-1', 'program-1', 'Gift program', '001234', '12500000', 'SAR');
        $offer = new OfferData('offer-1', 'program-1', '25% off', 'Example merchant', barcodeValue: 'offer-redemption-token');

        $giftPass = $wallet->createGiftCard($gift);
        $offerPass = $wallet->createOffer($offer);

        $this->assertIsString($giftPass);
        $this->assertIsString($offerPass);
        $giftFiles = $this->readPass($giftPass);
        $offerFiles = $this->readPass($offerPass);
        $giftDefinition = json_decode($giftFiles['pass.json'], true, 512, JSON_THROW_ON_ERROR);
        $offerDefinition = json_decode($offerFiles['pass.json'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('12.5 SAR', $giftDefinition['storeCard']['primaryFields'][0]['value']);
        $this->assertSame('25% off', $offerDefinition['coupon']['primaryFields'][0]['value']);
        $this->assertSame('offer-redemption-token', $offerDefinition['barcodes'][0]['message']);
        $this->assertArrayHasKey('icon.png', $giftFiles);
        $this->assertNull($wallet->getLastError());
    }

    public function test_embeds_each_program_logo_even_when_an_older_program_image_has_a_newer_timestamp(): void
    {
        $this->configureAppleSigning();
        $this->app->bind(AppleStampStripGenerator::class, fn (): AppleStampStripGenerator => new class extends AppleStampStripGenerator
        {
            public function generate(LoyaltyProgramData $program, MemberCardData $member): ?string
            {
                return null;
            }
        });
        $fixtures = Storage::disk('wallet-fixtures');
        $fixtures->put('program-a.png', 'program A logo');
        $fixtures->put('program-b.png', 'program B logo');
        touch($fixtures->path('program-a.png'), 1900000000);
        touch($fixtures->path('program-b.png'), 1800000000);
        $wallet = $this->app->make(AppleWalletService::class);
        $firstProgram = new LoyaltyProgramData('program-a', 'Program A', 10, 1, logoPath: $fixtures->path('program-a.png'));
        $secondProgram = new LoyaltyProgramData('program-b', 'Program B', 10, 1, logoPath: $fixtures->path('program-b.png'));
        $firstMember = new MemberCardData('card-a', 'qr-a', 1, 0, false, 'Member A');
        $secondMember = new MemberCardData('card-b', 'qr-b', 2, 0, false, 'Member B');

        $firstPass = $wallet->createPass($firstProgram, $firstMember);
        $secondPass = $wallet->createPass($secondProgram, $secondMember);

        $this->assertIsString($firstPass);
        $this->assertIsString($secondPass);
        $this->assertSame('program A logo', $this->readPass($firstPass)['logo.png']);
        $this->assertSame('program B logo', $this->readPass($secondPass)['logo.png']);
    }

    public function test_accepts_a_valid_certificate_store_with_an_empty_password(): void
    {
        $this->configureAppleSigning('');
        $wallet = $this->app->make(AppleWalletService::class);

        $pass = $wallet->createGiftCard(new GiftCardData('card-1', 'program-1', 'Gift program', '001234', '0', 'SAR'));

        $this->assertIsString($pass);
        $this->assertTrue($wallet->isConfigured());
        $this->assertSame('[empty]', $wallet->configurationReport()['certificate_store_password']['configured']);
    }

    public function test_returns_a_configuration_error_without_issuing_an_unsigned_pass(): void
    {
        config(['apple-wallet.pass_type_identifier' => '']);
        $wallet = $this->app->make(AppleWalletService::class);

        $pass = $wallet->createOffer(new OfferData('offer-1', 'program-1', '25% off', 'Example merchant'));

        $this->assertNull($pass);
        $this->assertNotNull($wallet->getLastError());
    }
}
