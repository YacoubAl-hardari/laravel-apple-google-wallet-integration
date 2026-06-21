<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Facades;

use Illuminate\Support\Facades\Facade;
use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleWalletService;

/**
 * @method static bool isConfigured()
 * @method static array<string, array<string, mixed>> configurationReport()
 * @method static string passIdentifier(\Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData $member)
 * @method static string|null createPass(\Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData $program, \Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData $member)
 * @method static string|null getLastError()
 *
 * @see AppleWalletService
 */
class AppleWallet extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppleWalletService::class;
    }
}
