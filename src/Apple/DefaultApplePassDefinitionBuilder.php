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
            'storeCard' => $this->buildStoreCard($program, $member, $required, $progress, $remaining, $memberName),
        ];

        event(new ApplePassDefinitionBuilding($program, $member, $definition));

        return $definition;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildStoreCard(
        LoyaltyProgramData $program,
        MemberCardData $member,
        int $required,
        int $progress,
        int $remaining,
        string $memberName,
    ): array {
        $fields = config('apple-wallet.fields', []);
        $visible = $fields['visible'] ?? ['rewards', 'remaining', 'member', 'status'];
        $secondaryOrder = $fields['secondary'] ?? ['rewards', 'remaining'];
        $auxiliaryOrder = $fields['auxiliary'] ?? ['member', 'status'];

        $secondaryMap = [
            'rewards' => [
                'key' => 'rewards',
                'label' => wallet_trans('rewards'),
                'value' => (string) $member->rewardsEarned,
            ],
            'remaining' => [
                'key' => 'remaining',
                'label' => wallet_trans('remaining'),
                'value' => (string) $remaining,
            ],
        ];

        $auxiliaryMap = [
            'member' => [
                'key' => 'member',
                'label' => wallet_trans('member'),
                'value' => $memberName !== '' ? $memberName : (string) $member->id,
            ],
            'status' => [
                'key' => 'status',
                'label' => wallet_trans('status'),
                'value' => $member->isCompleted
                    ? wallet_trans('status_completed')
                    : wallet_trans('status_in_progress'),
            ],
        ];

        return [
            'primaryFields' => [[
                'key' => 'balance',
                'label' => wallet_trans('stamps'),
                'value' => "{$progress} / {$required}",
            ]],
            'secondaryFields' => $this->pickFields($secondaryMap, $secondaryOrder, $visible),
            'auxiliaryFields' => $this->pickFields($auxiliaryMap, $auxiliaryOrder, $visible),
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
        ];
    }

    /**
     * @param  array<string, array<string, string>>  $map
     * @param  array<int, string>  $order
     * @param  array<int, string>  $visible
     * @return array<int, array<string, string>>
     */
    protected function pickFields(array $map, array $order, array $visible): array
    {
        $picked = [];

        foreach ($order as $key) {
            if (! in_array($key, $visible, true) || ! isset($map[$key])) {
                continue;
            }

            $picked[] = $map[$key];
        }

        return $picked;
    }
}
