<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Google;

use Google\Service\Walletobjects\Barcode;
use Google\Service\Walletobjects\BarcodeSectionDetail;
use Google\Service\Walletobjects\CardBarcodeSectionDetails;
use Google\Service\Walletobjects\ClassTemplateInfo;
use Google\Service\Walletobjects\FieldReference;
use Google\Service\Walletobjects\FieldSelector;
use Google\Service\Walletobjects\Image;
use Google\Service\Walletobjects\LoyaltyClass;
use Google\Service\Walletobjects\LoyaltyObject;
use Google\Service\Walletobjects\LoyaltyPoints;
use Google\Service\Walletobjects\LoyaltyPointsBalance;
use Google\Service\Walletobjects\Message;
use Google\Service\Walletobjects\TextModuleData;
use Illuminate\Support\Str;
use Yacoubalhaidari\AppleGoogleWallet\Contracts\BuildsGoogleLoyaltyPayload;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;
use Yacoubalhaidari\AppleGoogleWallet\Events\GoogleLoyaltyObjectBuilding;

class DefaultGoogleLoyaltyPayloadBuilder implements BuildsGoogleLoyaltyPayload
{
    public function __construct(
        protected WalletImageUrlResolver $imageUrlResolver,
    ) {
    }

    public function buildClass(LoyaltyProgramData $program): LoyaltyClass
    {
        $required = max(1, $program->requiredStamps);

        $payload = [
            'id' => $this->classId($program),
            'issuerName' => config('google-wallet.issuer_name'),
            'programName' => $program->name,
            'reviewStatus' => 'UNDER_REVIEW',
            'hexBackgroundColor' => $this->normalizeHexBackgroundColor(config('google-wallet.hex_background_color')),
            'messages' => [
                new Message([
                    'header' => wallet_trans('loyalty_program'),
                    'body' => $this->stampPromoMessage($program),
                    'messageType' => 'TEXT',
                ]),
            ],
            'textModulesData' => [
                new TextModuleData([
                    'id' => 'barcode_header',
                    'header' => '',
                    'body' => wallet_trans('barcode_footer'),
                ]),
                new TextModuleData([
                    'id' => 'reward_info',
                    'header' => wallet_trans('reward'),
                    'body' => wallet_trans('reward_description', [
                        'count' => $program->rewardCount,
                        'required' => $required,
                    ]),
                ]),
            ],
        ];

        $logoUri = $this->logoUri($program);
        if (!$logoUri) {
            throw new \RuntimeException(wallet_trans('google_logo_required'));
        }

        $payload['programLogo'] = new Image(['sourceUri' => ['uri' => $logoUri]]);

        if ($this->isStampLayout()) {
            $payload['classTemplateInfo'] = $this->buildStampClassTemplateInfo();
        }

        return new LoyaltyClass($payload);
    }

    public function buildObject(
        LoyaltyProgramData $program,
        MemberCardData $member,
        ?Image $heroImage = null,
    ): LoyaltyObject {
        $payload = [
            'id' => $this->objectId($member),
            'classId' => $this->classId($program),
            'state' => 'active',
            'accountName' => Str::limit(trim($member->memberName), 20, ''),
            'accountId' => (string) $member->id,
            'barcode' => new Barcode([
                'type' => 'qrCode',
                'value' => $member->qrCode,
                'alternateText' => ' ',
            ]),
            'loyaltyPoints' => $this->buildStampPoints($member, $program),
            'textModulesData' => $this->buildTextModules($member, $program),
        ];

        $visible = config('google-wallet.fields.visible', ['rewards', 'remaining', 'status']);
        if (in_array('rewards', $visible, true)) {
            $payload['secondaryLoyaltyPoints'] = new LoyaltyPoints([
                'label' => Str::limit(wallet_trans('rewards'), 9, ''),
                'balance' => new LoyaltyPointsBalance([
                    'int' => $member->rewardsEarned,
                ]),
            ]);
        }

        if ($heroImage) {
            $payload['heroImage'] = $heroImage;
        }

        $object = new LoyaltyObject($payload);

        event(new GoogleLoyaltyObjectBuilding($program, $member, $object));

        return $object;
    }

    public function classId(LoyaltyProgramData $program): string
    {
        return config('google-wallet.issuer_id') . '.stamp_' . $this->slug($program->name);
    }

    public function objectId(MemberCardData $member): string
    {
        return config('google-wallet.issuer_id') . '.member_' . $this->slug((string) $member->id);
    }

    protected function slug(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '_', $value);
    }

    protected function logoUri(?LoyaltyProgramData $program = null): ?string
    {
        if ($program?->imageUrl) {
            return $this->imageUrlResolver->resolve($program->imageUrl);
        }

        return $this->imageUrlResolver->resolve(config('google-wallet.default_logo'));
    }

    protected function isStampLayout(): bool
    {
        return config('google-wallet.card_layout', 'stamp') === 'stamp';
    }

    protected function normalizeHexBackgroundColor(?string $color): string
    {
        $color = trim((string) $color);

        if ($color === '') {
            $color = '#8B5E3C';
        }

        if ($color[0] !== '#') {
            $color = '#' . $color;
        }

        if (!preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $color)) {
            return '#8B5E3C';
        }

        return strtoupper($color);
    }

    protected function stampPromoMessage(LoyaltyProgramData $program): string
    {
        $required = max(1, $program->requiredStamps);
        $reward = max(1, $program->rewardCount);

        return wallet_trans('promo_message', [
            'required' => $required,
            'reward' => $reward,
        ]);
    }

    protected function buildStampClassTemplateInfo(): ClassTemplateInfo
    {
        $headerField = new FieldReference(['fieldPath' => "class.textModulesData['barcode_header']"]);
        $headerSelector = new FieldSelector(['fields' => [$headerField]]);

        return new ClassTemplateInfo([
            'cardBarcodeSectionDetails' => new CardBarcodeSectionDetails([
                'firstTopDetail' => new BarcodeSectionDetail([
                    'fieldSelector' => $headerSelector,
                ]),
            ]),
        ]);
    }

    protected function buildStampPoints(MemberCardData $member, LoyaltyProgramData $program): LoyaltyPoints
    {
        $required = max(1, $program->requiredStamps);
        $progress = min($required, max(0, $member->stampsProgress));

        return new LoyaltyPoints([
            'label' => Str::limit(wallet_trans('stamps'), 9, ''),
            'balance' => new LoyaltyPointsBalance([
                'string' => "{$progress} / {$required}",
            ]),
        ]);
    }

    /**
     * @return array<int, TextModuleData>
     */
    protected function buildTextModules(MemberCardData $member, LoyaltyProgramData $program): array
    {
        $fields = config('google-wallet.fields', []);
        $visible = $fields['visible'] ?? ['remaining', 'status'];
        $order = $fields['modules'] ?? ['remaining', 'status'];

        $map = [
            'remaining' => new TextModuleData([
                'id' => 'visits_remaining',
                'header' => wallet_trans('remaining'),
                'body' => (string) max(0, $program->requiredStamps - $member->stampsProgress),
            ]),
            'status' => new TextModuleData([
                'id' => 'stamp_status',
                'header' => wallet_trans('status'),
                'body' => $member->isCompleted
                    ? wallet_trans('status_completed')
                    : wallet_trans('status_in_progress'),
            ]),
        ];

        $modules = [];
        foreach ($order as $key) {
            if (in_array($key, $visible, true) && isset($map[$key])) {
                $modules[] = $map[$key];
            }
        }

        return $modules;
    }
}
