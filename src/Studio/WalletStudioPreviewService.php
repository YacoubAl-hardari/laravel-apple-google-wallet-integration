<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Studio;

use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleStampStripGenerator;
use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleWalletService;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;
use Yacoubalhaidari\AppleGoogleWallet\Google\GoogleWalletService;
use Yacoubalhaidari\AppleGoogleWallet\Google\StampCardImageGenerator;

class WalletStudioPreviewService
{
    public function __construct(
        protected WalletStudioConfigApplier $applier,
        protected AppleStampStripGenerator $appleStripGenerator,
        protected StampCardImageGenerator $googleStripGenerator,
        protected AppleWalletService $appleWalletService,
        protected GoogleWalletService $googleWalletService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function generate(array $input): array
    {
        $this->applier->apply($input);

        $program = new LoyaltyProgramData(
            id: 'studio',
            name: (string) ($input['preview_program'] ?? 'Preview'),
            requiredStamps: max(1, (int) ($input['preview_stamps_total'] ?? 10)),
            rewardCount: 1,
            imageUrl: $input['logo_url'] ?? null,
        );

        $member = new MemberCardData(
            id: 'studio',
            qrCode: 'STUDIO-PREVIEW-QR',
            stampsProgress: max(0, (int) ($input['preview_stamps_filled'] ?? 3)),
            rewardsEarned: max(0, (int) ($input['preview_rewards'] ?? 0)),
            isCompleted: (int) ($input['preview_stamps_filled'] ?? 0) >= (int) ($input['preview_stamps_total'] ?? 10),
            memberName: (string) ($input['preview_member'] ?? 'Preview'),
        );

        $platform = (string) ($input['platform'] ?? 'apple');
        $result = [];

        if ($platform === 'apple') {
            $result['apple_strip_url'] = $this->normalizePublicUrl(
                $this->appleStripGenerator->generate($program, $member)
            );
            $result['apple_configured'] = $this->appleWalletService->isConfigured();
            $result['apple_pass_ready'] = $result['apple_configured']
                && $this->appleWalletService->createPass($program, $member) !== null;
        }

        if ($platform === 'google') {
            $result['google_strip_url'] = $this->normalizePublicUrl(
                $this->googleStripGenerator->generate($program, $member)
            );
            $result['google_configured'] = $this->googleWalletService->isConfigured();
            $result['google_save_url'] = $result['google_configured']
                ? $this->googleWalletService->saveUrl($program, $member)
                : null;
            $result['google_error'] = $result['google_save_url']
                ? null
                : $this->googleWalletService->getLastError();
        }

        return $result;
    }

    protected function normalizePublicUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            $path = str_starts_with($url, '/')
                ? $url
                : '/storage/' . ltrim(str_replace('\\', '/', $url), '/');
        }

        return url($path);
    }
}
