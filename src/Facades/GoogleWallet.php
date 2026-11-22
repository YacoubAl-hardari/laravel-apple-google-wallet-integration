<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Facades;

use Illuminate\Support\Facades\Facade;
use Yacoubalhaidari\AppleGoogleWallet\Google\GoogleWalletService;

/**
 * @method static bool isConfigured()
 * @method static array<string, array<string, mixed>> configurationReport()
 * @method static string createLoyaltyCard(\Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData $program, \Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData $member)
 * @method static string|null saveUrl(\Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData $program, \Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData $member)
 * @method static bool objectExists(\Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData $member)
 * @method static bool updateLoyaltyCard(\Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData $program, \Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData $member)
 * @method static string createGiftCard(\Yacoubalhaidari\AppleGoogleWallet\DTOs\GiftCardData $card)
 * @method static bool updateGiftCard(\Yacoubalhaidari\AppleGoogleWallet\DTOs\GiftCardData $card, bool $notify = false)
 * @method static string createOffer(\Yacoubalhaidari\AppleGoogleWallet\DTOs\OfferData $offer)
 * @method static bool updateOffer(\Yacoubalhaidari\AppleGoogleWallet\DTOs\OfferData $offer, bool $notify = false)
 * @method static void sendMessage(string $passType, string $objectId, string $header, string $body, bool $notify = false)
 *
 * @see GoogleWalletService
 */
class GoogleWallet extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GoogleWalletService::class;
    }
}
