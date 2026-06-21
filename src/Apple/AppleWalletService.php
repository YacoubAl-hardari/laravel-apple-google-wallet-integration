<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Apple;

use Illuminate\Support\Facades\Log;
use Yacoubalhaidari\AppleGoogleWallet\Concerns\ResolvesPaths;
use Yacoubalhaidari\AppleGoogleWallet\Contracts\BuildsApplePassDefinition;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;

class AppleWalletService
{
    use ResolvesPaths;

    protected ?string $lastError = null;

    public function __construct(
        protected AppleStampStripGenerator $stampStripGenerator,
        protected BuildsApplePassDefinition $passDefinitionBuilder,
    ) {
    }

    public function isConfigured(): bool
    {
        return collect($this->configurationReport())->every(fn (array $item) => (bool) ($item['ok'] ?? false));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function configurationReport(): array
    {
        $passType = (string) config('apple-wallet.pass_type_identifier');
        $teamId = (string) config('apple-wallet.team_identifier');
        $certPath = $this->resolvePath((string) config('apple-wallet.certificate_store_path'));
        $wwdrPath = $this->resolvePath((string) config('apple-wallet.wwdr_certificate_path'));
        $iconPath = $this->resolvePath((string) config('apple-wallet.icon_path'));
        $certPassword = (string) config('apple-wallet.certificate_store_password');

        return [
            'pass_type_identifier' => [
                'ok' => $passType !== '',
                'value' => $passType !== '' ? $passType : null,
            ],
            'team_identifier' => [
                'ok' => $teamId !== '',
                'value' => $teamId !== '' ? $teamId : null,
            ],
            'certificate_store_path' => [
                'ok' => is_readable($certPath),
                'configured' => config('apple-wallet.certificate_store_path'),
                'resolved' => $certPath,
            ],
            'certificate_store_password' => [
                'ok' => $certPassword !== '',
                'configured' => $certPassword !== '' ? '[set]' : '[empty]',
            ],
            'wwdr_certificate_path' => [
                'ok' => is_readable($wwdrPath),
                'configured' => config('apple-wallet.wwdr_certificate_path'),
                'resolved' => $wwdrPath,
            ],
            'icon_path' => [
                'ok' => is_readable($iconPath),
                'configured' => config('apple-wallet.icon_path'),
                'resolved' => $iconPath,
            ],
        ];
    }

    public function passIdentifier(MemberCardData $member): string
    {
        return 'loyalty_member_' . $member->id;
    }

    public function createPass(LoyaltyProgramData $program, MemberCardData $member): ?string
    {
        if (!$this->isConfigured()) {
            Log::debug('AppleWalletService.createPass skipped', [
                'member_id' => $member->id,
                'reason' => 'not_configured',
                'checks' => $this->configurationReport(),
            ]);

            return null;
        }

        try {
            $passId = $this->passIdentifier($member);

            Log::debug('AppleWalletService.createPass start', [
                'pass_id' => $passId,
                'member_id' => $member->id,
                'program_id' => $program->id,
            ]);

            $this->applyResolvedCertificatePaths();

            $pass = new PkPassGenerator($passId, true);
            $pass->setPassDefinition($this->passDefinitionBuilder->build($program, $member));

            $assets = $this->resolveAssets($program, $member);

            Log::debug('AppleWalletService.createPass assets', [
                'pass_id' => $passId,
                'assets' => $assets,
            ]);

            foreach ($assets as $assetPath) {
                $pass->addAsset($assetPath);
            }

            $pkpass = $pass->create();

            Log::debug('AppleWalletService.createPass success', [
                'pass_id' => $passId,
                'bytes' => strlen($pkpass),
            ]);

            $this->lastError = null;

            return $pkpass;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();

            Log::error('AppleWalletService.createPass failed', [
                'member_id' => $member->id,
                'program_id' => $program->id,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'checks' => $this->configurationReport(),
            ]);

            return null;
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * @return array<int, string>
     */
    protected function resolveAssets(LoyaltyProgramData $program, MemberCardData $member): array
    {
        $assets = [];

        $iconPath = $this->resolvePath((string) config('apple-wallet.icon_path'));
        if (is_readable($iconPath)) {
            $assets[] = $this->copyAssetAs($iconPath, 'icon.png');
        }

        $logoPath = $this->resolvePath((string) config('apple-wallet.logo_path'));
        if (is_readable($logoPath)) {
            $assets[] = $this->copyAssetAs($logoPath, 'logo.png');
        }

        $stripPath = $this->resolveStripPath($program, $member);
        if ($stripPath) {
            $assets[] = $this->copyAssetAs($stripPath, 'strip.png');
        }

        return array_values(array_filter($assets));
    }

    protected function resolveStripPath(LoyaltyProgramData $program, MemberCardData $member): ?string
    {
        $url = $this->stampStripGenerator->generate($program, $member);
        if (!$url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || !str_contains($path, '/storage/')) {
            return null;
        }

        $relative = ltrim(substr($path, strpos($path, '/storage/') + strlen('/storage/')), '/');
        $local = storage_path('app/public/' . $relative);

        return is_file($local) ? $local : null;
    }

    protected function copyAssetAs(string $sourcePath, string $targetFilename): string
    {
        $tempDir = storage_path('app/apple-wallet/assets');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $targetPath = $tempDir . DIRECTORY_SEPARATOR . $targetFilename;

        if (!is_file($targetPath) || filemtime($targetPath) < filemtime($sourcePath)) {
            copy($sourcePath, $targetPath);
        }

        return $targetPath;
    }

    protected function applyResolvedCertificatePaths(): void
    {
        config([
            'apple-wallet.certificate_store_path' => $this->resolvePath(
                (string) config('apple-wallet.certificate_store_path')
            ),
            'apple-wallet.wwdr_certificate_path' => $this->resolvePath(
                (string) config('apple-wallet.wwdr_certificate_path')
            ),
        ]);
    }
}
