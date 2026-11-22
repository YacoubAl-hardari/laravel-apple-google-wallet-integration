<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Apple;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Yacoubalhaidari\AppleGoogleWallet\Concerns\ResolvesPaths;
use Yacoubalhaidari\AppleGoogleWallet\Contracts\BuildsApplePassDefinition;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\GiftCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\OfferData;

class AppleWalletService
{
    use ResolvesPaths;

    protected ?string $lastError = null;

    public function __construct(
        protected AppleStampStripGenerator $stampStripGenerator,
        protected BuildsApplePassDefinition $passDefinitionBuilder,
        protected ?DefaultAppleRetailPassDefinitionBuilder $retailPassDefinitionBuilder = null,
    ) {}

    public function isConfigured(): bool
    {
        return collect($this->configurationReport())->every(fn (array $item): bool => (bool) ($item['ok'] ?? false));
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
        $certificates = [];
        $certificateStore = is_file($certPath) && is_readable($certPath) ? file_get_contents($certPath) : false;
        $canOpenCertificate = $certificateStore !== false
            && @openssl_pkcs12_read($certificateStore, $certificates, $certPassword);

        return [
            'pass_type_identifier' => ['ok' => $passType !== '', 'value' => $passType !== '' ? $passType : null],
            'team_identifier' => ['ok' => $teamId !== '', 'value' => $teamId !== '' ? $teamId : null],
            'certificate_store_path' => [
                'ok' => $canOpenCertificate,
                'configured' => config('apple-wallet.certificate_store_path'),
                'resolved' => $certPath,
            ],
            'certificate_store_password' => [
                'ok' => $canOpenCertificate,
                'configured' => $certPassword !== '' ? '[set]' : '[empty]',
            ],
            'wwdr_certificate_path' => [
                'ok' => is_file($wwdrPath) && is_readable($wwdrPath)
                    && @openssl_x509_read(file_get_contents($wwdrPath)) !== false,
                'configured' => config('apple-wallet.wwdr_certificate_path'),
                'resolved' => $wwdrPath,
            ],
            'icon_path' => [
                'ok' => is_file($iconPath) && is_readable($iconPath),
                'configured' => config('apple-wallet.icon_path'),
                'resolved' => $iconPath,
            ],
        ];
    }

    public function passIdentifier(MemberCardData $member): string
    {
        return 'loyalty_member_'.$member->id;
    }

    public function createPass(LoyaltyProgramData $program, MemberCardData $member): ?string
    {
        return $this->createSignedPass(
            fn (): array => $this->passDefinitionBuilder->build($program, $member),
            fn (): array => $this->resolveAssets($program, $member),
            $this->passIdentifier($member),
        );
    }

    public function createGiftCard(GiftCardData $card): ?string
    {
        return $this->createSignedPass(
            fn (): array => $this->retailBuilder()->buildGiftCard($card),
            fn (): array => $this->resolveBaseAssets(),
        );
    }

    public function createOffer(OfferData $offer): ?string
    {
        return $this->createSignedPass(
            fn (): array => $this->retailBuilder()->buildOffer($offer),
            fn (): array => $this->resolveBaseAssets(),
        );
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * @param  Closure(): array<string, mixed>  $definition
     * @param  Closure(): array<int|string, string>  $assets
     */
    protected function createSignedPass(Closure $definition, Closure $assets, ?string $passId = null): ?string
    {
        if (! $this->isConfigured()) {
            $this->lastError = 'Apple Wallet signing credentials or required assets are not configured.';

            return null;
        }

        try {
            $this->applyResolvedCertificatePaths();
            $passDefinition = $definition();
            $passId ??= (string) $passDefinition['serialNumber'];
            $pass = new PkPassGenerator($passId, true);
            $pass->setPassDefinition($passDefinition);

            foreach ($assets() as $archiveName => $assetPath) {
                $pass->addAsset($assetPath, is_string($archiveName) ? $archiveName : null);
            }

            $pkpass = $pass->create();
            $this->lastError = null;

            return $pkpass;
        } catch (Throwable $exception) {
            $this->lastError = $exception->getMessage();

            Log::error('Apple Wallet pass creation failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    protected function resolveAssets(LoyaltyProgramData $program, MemberCardData $member): array
    {
        $assets = $this->resolveBaseAssets($program->logoPath);
        $stripPath = $this->resolveStripPath($program, $member);

        if ($stripPath !== null) {
            $assets['strip.png'] = $stripPath;
        }

        return $assets;
    }

    /**
     * @return array<string, string>
     */
    protected function resolveBaseAssets(?string $programLogoPath = null): array
    {
        $assets = [];
        $logoPath = $programLogoPath === null ? '' : $this->resolvePath($programLogoPath);
        $paths = [
            'icon' => $this->resolvePath((string) config('apple-wallet.icon_path')),
            'logo' => is_file($logoPath) && is_readable($logoPath)
                ? $logoPath
                : $this->resolvePath((string) config('apple-wallet.logo_path')),
        ];

        foreach ($paths as $name => $path) {
            if (is_file($path) && is_readable($path)) {
                $assets[$name.'.png'] = $path;

                foreach ([2, 3] as $scale) {
                    $variant = dirname($path).DIRECTORY_SEPARATOR.pathinfo($path, PATHINFO_FILENAME).'@'.$scale.'x.png';

                    if (is_file($variant) && is_readable($variant)) {
                        $assets[$name.'@'.$scale.'x.png'] = $variant;
                    }
                }
            }
        }

        return $assets;
    }

    protected function resolveStripPath(LoyaltyProgramData $program, MemberCardData $member): ?string
    {
        $url = $this->stampStripGenerator->generate($program, $member);

        if ($url === null) {
            return null;
        }

        $disk = Storage::disk((string) config('apple-wallet.public_disk', 'public'));
        $path = parse_url($url, PHP_URL_PATH);
        $basePath = parse_url($disk->url(''), PHP_URL_PATH);

        if (! is_string($path) || ! is_string($basePath)
            || ! str_starts_with($path, rtrim($basePath, '/').'/')) {
            return null;
        }

        $relative = ltrim(substr($path, strlen(rtrim($basePath, '/'))), '/');
        $local = $disk->path($relative);

        return is_file($local) && is_readable($local) ? $local : null;
    }

    protected function applyResolvedCertificatePaths(): void
    {
        config([
            'apple-wallet.certificate_store_path' => $this->resolvePath((string) config('apple-wallet.certificate_store_path')),
            'apple-wallet.wwdr_certificate_path' => $this->resolvePath((string) config('apple-wallet.wwdr_certificate_path')),
        ]);
    }

    protected function retailBuilder(): DefaultAppleRetailPassDefinitionBuilder
    {
        return $this->retailPassDefinitionBuilder ??= new DefaultAppleRetailPassDefinitionBuilder;
    }
}
