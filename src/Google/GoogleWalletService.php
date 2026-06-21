<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Google;

use Firebase\JWT\JWT;
use Google\Client;
use Google\Service\Walletobjects;
use Google\Service\Walletobjects\Image;
use Google\Service\Walletobjects\LoyaltyClass;
use Google\Service\Walletobjects\LoyaltyObject;
use Illuminate\Support\Facades\Log;
use Yacoubalhaidari\AppleGoogleWallet\Concerns\ResolvesPaths;
use Yacoubalhaidari\AppleGoogleWallet\Contracts\BuildsGoogleLoyaltyPayload;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;

class GoogleWalletService
{
    use ResolvesPaths;

    protected ?string $lastError = null;

    public function __construct(
        protected StampCardImageGenerator $stampCardImageGenerator,
        protected WalletImageUrlResolver $imageUrlResolver,
        protected BuildsGoogleLoyaltyPayload $payloadBuilder,
    ) {
    }

    public function isConfigured(): bool
    {
        $issuerId = config('google-wallet.issuer_id');
        $credentialsPath = $this->credentialsPath();

        return !empty($issuerId) && is_readable($credentialsPath);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function configurationReport(): array
    {
        $issuerId = (string) config('google-wallet.issuer_id');
        $credentialsPath = $this->credentialsPath();

        return [
            'issuer_id' => [
                'ok' => $issuerId !== '',
                'value' => $issuerId !== '' ? $issuerId : null,
            ],
            'service_account_json' => [
                'ok' => is_readable($credentialsPath),
                'configured' => config('google-wallet.service_account_json'),
                'resolved' => $credentialsPath,
            ],
        ];
    }

    public function createLoyaltyCard(LoyaltyProgramData $program, MemberCardData $member): string
    {
        $client = $this->getClient();
        $service = new Walletobjects($client);

        $this->upsertLoyaltyClass($service, $program);

        $object = $this->buildObject($program, $member);

        try {
            $service->loyaltyobject->insert($object);
        } catch (\Google\Service\Exception $e) {
            if ((int) $e->getCode() === 409) {
                $service->loyaltyobject->patch($this->payloadBuilder->objectId($member), $object);
            } else {
                Log::error('GoogleWalletService LoyaltyObject Exception: ' . $e->getMessage());
                throw $e;
            }
        }

        return $this->buildSaveUrl($this->payloadBuilder->objectId($member));
    }

    public function saveUrl(LoyaltyProgramData $program, MemberCardData $member): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $client = $this->getClient();
            $service = new Walletobjects($client);
            $this->upsertLoyaltyClass($service, $program);

            return $this->buildSaveUrlWithObjects([
                $this->objectPayloadArray($program, $member),
            ]);
        } catch (\Throwable $e) {
            $this->lastError = $this->humanizeError($e->getMessage());
            Log::error('GoogleWalletService saveUrl: ' . $e->getMessage());

            return null;
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    protected function humanizeError(string $message): string
    {
        if (str_contains($message, 'Invalid image URL') || str_contains($message, 'Could not load image')) {
            return 'Google لا يستطيع تحميل الشعار — تأكد أن GOOGLE_WALLET_FALLBACK_LOGO يرجّع صورة PNG/JPG حقيقية (ليس صفحة HTML).';
        }

        if (str_contains($message, 'SSL certificate problem')) {
            return 'خطأ SSL محلي عند الاتصال بـ Google — راجع إعدادات cacert.pem في PHP.';
        }

        return $message;
    }

    public function objectExists(MemberCardData $member): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $client = $this->getClient();
            $service = new Walletobjects($client);
            $service->loyaltyobject->get($this->payloadBuilder->objectId($member));

            return true;
        } catch (\Google\Service\Exception $e) {
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function updateLoyaltyCard(LoyaltyProgramData $program, MemberCardData $member): bool
    {
        $client = $this->getClient();
        $service = new Walletobjects($client);
        $objectId = $this->payloadBuilder->objectId($member);

        try {
            $loyaltyObject = $service->loyaltyobject->get($objectId);
            $updated = $this->buildObject($program, $member);

            $loyaltyObject->setLoyaltyPoints($updated->getLoyaltyPoints());
            $loyaltyObject->setSecondaryLoyaltyPoints($updated->getSecondaryLoyaltyPoints());
            $loyaltyObject->setTextModulesData($updated->getTextModulesData());

            $heroImage = $updated->getHeroImage();
            if ($heroImage) {
                $loyaltyObject->setHeroImage($heroImage);
                $loyaltyObject->setImageModulesData([]);
            }

            $service->loyaltyobject->patch($objectId, $loyaltyObject);

            return true;
        } catch (\Google\Service\Exception $e) {
            if ((int) $e->getCode() !== 404) {
                return false;
            }

            try {
                $this->createLoyaltyCard($program, $member);

                return true;
            } catch (\Exception $ex) {
                return false;
            }
        }
    }

    protected function upsertLoyaltyClass(Walletobjects $service, LoyaltyProgramData $program): void
    {
        $loyaltyClass = $this->payloadBuilder->buildClass($program);

        try {
            $service->loyaltyclass->insert($loyaltyClass);
        } catch (\Google\Service\Exception $e) {
            if ((int) $e->getCode() !== 409) {
                throw $e;
            }

            $service->loyaltyclass->patch($this->payloadBuilder->classId($program), $loyaltyClass);
        }
    }

    protected function buildObject(LoyaltyProgramData $program, MemberCardData $member): LoyaltyObject
    {
        return $this->payloadBuilder->buildObject(
            $program,
            $member,
            $this->resolveHeroImage($program, $member),
        );
    }

    protected function resolveHeroImage(LoyaltyProgramData $program, MemberCardData $member): ?Image
    {
        if (config('google-wallet.card_layout', 'stamp') !== 'stamp') {
            $logoUri = $this->payloadBuilder instanceof DefaultGoogleLoyaltyPayloadBuilder
                ? $this->imageUrlResolver->resolve($program->imageUrl ?? config('google-wallet.default_logo'))
                : null;

            return $logoUri ? new Image(['sourceUri' => ['uri' => $logoUri]]) : null;
        }

        $stampStripUrl = $this->stampCardImageGenerator->generate($program, $member);
        $resolved = $this->imageUrlResolver->resolveOptional($stampStripUrl);

        return $resolved ? new Image(['sourceUri' => ['uri' => $resolved]]) : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function objectPayloadArray(LoyaltyProgramData $program, MemberCardData $member): array
    {
        $loyaltyObject = $this->buildObject($program, $member);

        return json_decode(json_encode($loyaltyObject), true) ?: [];
    }

    protected function buildSaveUrlWithObjects(array $objects): string
    {
        $claims = [
            'iss' => $this->credentials()['client_email'],
            'aud' => 'google',
            'typ' => 'savetowallet',
            'payload' => [
                'loyaltyObjects' => $objects,
            ],
        ];

        $jwt = JWT::encode($claims, $this->credentials()['private_key'], 'RS256');

        return "https://pay.google.com/gp/v/save/{$jwt}";
    }

    protected function buildSaveUrl(string $objectId): string
    {
        $claims = [
            'iss' => $this->credentials()['client_email'],
            'aud' => 'google',
            'typ' => 'savetowallet',
            'payload' => [
                'loyaltyObjects' => [
                    ['id' => $objectId],
                ],
            ],
        ];

        $jwt = JWT::encode($claims, $this->credentials()['private_key'], 'RS256');

        return "https://pay.google.com/gp/v/save/{$jwt}";
    }

    protected function credentialsPath(): string
    {
        return $this->resolvePath((string) config('google-wallet.service_account_json'));
    }

    protected function getClient(): Client
    {
        $client = new Client();
        $client->setAuthConfig($this->credentialsPath());
        $client->addScope('https://www.googleapis.com/auth/wallet_object.issuer');

        return $client;
    }

    /**
     * @return array<string, mixed>
     */
    protected function credentials(): array
    {
        return json_decode(file_get_contents($this->credentialsPath()), true);
    }
}
