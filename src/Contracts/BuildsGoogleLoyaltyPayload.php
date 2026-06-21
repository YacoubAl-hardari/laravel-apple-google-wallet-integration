<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Contracts;

use Google\Service\Walletobjects\LoyaltyClass;
use Google\Service\Walletobjects\LoyaltyObject;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;

interface BuildsGoogleLoyaltyPayload
{
    public function buildClass(LoyaltyProgramData $program): LoyaltyClass;

    public function buildObject(
        LoyaltyProgramData $program,
        MemberCardData $member,
        ?\Google\Service\Walletobjects\Image $heroImage = null,
    ): LoyaltyObject;
}
