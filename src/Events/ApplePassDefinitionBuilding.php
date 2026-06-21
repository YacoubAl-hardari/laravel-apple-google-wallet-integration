<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Events;

use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;

class ApplePassDefinitionBuilding
{
    /**
     * @param  array<string, mixed>  $definition
     */
    public function __construct(
        public LoyaltyProgramData $program,
        public MemberCardData $member,
        public array $definition,
    ) {
    }
}
