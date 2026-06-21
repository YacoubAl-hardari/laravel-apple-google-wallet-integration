<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Apple;

use Illuminate\Support\Str;
use Yacoubalhaidari\AppleGoogleWallet\Contracts\BuildsApplePassDefinition;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;
use Yacoubalhaidari\AppleGoogleWallet\Events\ApplePassDefinitionBuilding;
use Yacoubalhaidari\AppleGoogleWallet\Support\AppleColorNormalizer;

class DefaultApplePassDefinitionBuilder implements BuildsApplePassDefinition
{
    /**
     * @return array<string, mixed>
     */
    public function build(LoyaltyProgramData $program, MemberCardData $member): array
    {
        $required = max(1, $program->requiredStamps);
        $progress = min($required, max(0, $member->stampsProgress));
        $remaining = max(0, $required - $progress);
        $memberName = Str::limit(trim($member->memberName), 20, '');

        $barcode = [
            'message' => $member->qrCode,
            'format' => 'PKBarcodeFormatQR',
            'altText' => (string) config('apple-wallet.barcode_alt_text', ' '),
            'messageEncoding' => 'utf-8',
        ];

        $definition = [
            'description' => $program->name,
            'formatVersion' => 1,
            'organizationName' => (string) config('apple-wallet.organization_name'),
            'passTypeIdentifier' => (string) config('apple-wallet.pass_type_identifier'),
            'serialNumber' => (string) $member->id,
            'teamIdentifier' => (string) config('apple-wallet.team_identifier'),
            'foregroundColor' => AppleColorNormalizer::normalize(config('apple-wallet.foreground_color'), 'rgb(255, 255, 255)'),
            'backgroundColor' => AppleColorNormalizer::normalize(config('apple-wallet.background_color'), 'rgb(139, 94, 60)'),
            'labelColor' => AppleColorNormalizer::normalize(config('apple-wallet.label_color'), 'rgb(255, 255, 255)'),
            'barcode' => $barcode,
            'barcodes' => [$barcode],
            'storeCard' => [
                'primaryFields' => [
                    [
                        'key' => 'balance',
                        'label' => wallet_trans('stamps'),
                        'value' => "{$progress} / {$required}",
                    ],
                ],
                'secondaryFields' => [
                    [
                        'key' => 'rewards',
                        'label' => wallet_trans('rewards'),
                        'value' => (string) $member->rewardsEarned,
                    ],
                    [
                        'key' => 'remaining',
                        'label' => wallet_trans('remaining'),
                        'value' => (string) $remaining,
                    ],
                ],
                'auxiliaryFields' => [
                    [
                        'key' => 'member',
                        'label' => wallet_trans('member'),
                        'value' => $memberName !== '' ? $memberName : (string) $member->id,
                    ],
                    [
                        'key' => 'status',
                        'label' => wallet_trans('status'),
                        'value' => $member->isCompleted
                            ? wallet_trans('status_completed')
                            : wallet_trans('status_in_progress'),
                    ],
                ],
                'backFields' => [
                    [
                        'key' => 'program',
                        'label' => wallet_trans('program'),
                        'value' => $program->name,
                    ],
                    [
                        'key' => 'reward',
                        'label' => wallet_trans('reward'),
                        'value' => wallet_trans('reward_description', [
                            'count' => $program->rewardCount,
                            'required' => $required,
                        ]),
                    ],
                    [
                        'key' => 'scan_code',
                        'label' => wallet_trans('card_code'),
                        'value' => $member->qrCode,
                    ],
                ],
            ],
        ];

        event(new ApplePassDefinitionBuilding($program, $member, $definition));

        return $definition;
    }
}
