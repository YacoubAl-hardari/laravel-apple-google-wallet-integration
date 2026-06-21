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
