<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Events;

use Google\Service\Walletobjects\LoyaltyObject;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;

class GoogleLoyaltyObjectBuilding
{
    public function __construct(
        public LoyaltyProgramData $program,
        public MemberCardData $member,
        public LoyaltyObject $object,
    ) {
    }
}
