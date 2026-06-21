<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Contracts;

use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;

interface BuildsApplePassDefinition
{
    /**
     * @return array<string, mixed>
     */
    public function build(LoyaltyProgramData $program, MemberCardData $member): array;
}
