<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Google;

use Firebase\JWT\JWT;
use Google\Client;
use Google\Model;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\Walletobjects;
use Google\Service\Walletobjects\AddMessageRequest;
use Google\Service\Walletobjects\DateTime as WalletDateTime;
use Google\Service\Walletobjects\Image;
use Google\Service\Walletobjects\LoyaltyObject;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Yacoubalhaidari\AppleGoogleWallet\Concerns\ResolvesPaths;
use Yacoubalhaidari\AppleGoogleWallet\Contracts\BuildsGoogleLoyaltyPayload;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\GiftCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\OfferData;
use Yacoubalhaidari\AppleGoogleWallet\Support\RetailPassValidation;

class GoogleWalletService
{
    use ResolvesPaths;

    protected ?string $lastError = null;

    public function __construct(
        protected StampCardImageGenerator $stampCardImageGenerator,
        protected WalletImageUrlResolver $imageUrlResolver,
        protected BuildsGoogleLoyaltyPayload $payloadBuilder,
        protected ?DefaultGoogleRetailPayloadBuilder $retailPayloadBuilder = null,
    ) {}

    public function isConfigured(): bool
    {
        try {
            $this->credentials();

            return (bool) preg_match('/^[0-9]+$/D', (string) config('google-wallet.issuer_id'));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function configurationReport(): array
    {
        $issuerId = (string) config('google-wallet.issuer_id');
        $credentialsPath = $this->credentialsPath();
        $credentialsValid = true;

        try {
            $this->credentials();
        } catch (\Throwable) {
            $credentialsValid = false;
        }

        return [
            'issuer_id' => [
                'ok' => (bool) preg_match('/^[0-9]+$/D', $issuerId),
                'value' => $issuerId !== '' ? $issuerId : null,
            ],
            'service_account_json' => [
                'ok' => $credentialsValid,
                'configured' => config('google-wallet.service_account_json'),
                'resolved' => $credentialsPath,
            ],
        ];
    }

    public function createLoyaltyCard(LoyaltyProgramData $program, MemberCardData $member): string
    {
        $service = $this->getService();

        $this->upsertLoyaltyClass($service, $program);

        $object = $this->buildObject($program, $member);

        try {
            $service->loyaltyobject->insert($object);
        } catch (GoogleServiceException $e) {
            if ((int) $e->getCode() === 409) {
                $service->loyaltyobject->patch($this->payloadBuilder->objectId($member), $object);
            } else {
                Log::error('GoogleWalletService LoyaltyObject Exception: '.$e->getMessage());
                throw $e;
            }
        }

        return $this->buildSaveUrl($this->payloadBuilder->objectId($member));
    }

    public function saveUrl(LoyaltyProgramData $program, MemberCardData $member): ?string
    {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $service = $this->getService();
            $this->upsertLoyaltyClass($service, $program);

            return $this->buildSaveUrlWithObjects([
                $this->objectPayloadArray($program, $member),
            ]);
        } catch (\Throwable $e) {
            $this->lastError = $this->humanizeError($e->getMessage());
            Log::error('GoogleWalletService saveUrl: '.$e->getMessage());

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
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $service = $this->getService();
            $service->loyaltyobject->get($this->payloadBuilder->objectId($member));

            return true;
        } catch (GoogleServiceException $e) {
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function updateLoyaltyCard(LoyaltyProgramData $program, MemberCardData $member): bool
    {
        $service = $this->getService();
        $objectId = $this->payloadBuilder->objectId($member);

        try {
            $updated = $this->buildObject($program, $member);
            $this->upsertLoyaltyClass($service, $program);
            $service->loyaltyobject->patch($objectId, $updated);

            return true;
        } catch (GoogleServiceException $e) {
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

    public function createGiftCard(GiftCardData $card): string
    {
        $builder = $this->retailBuilder();

        $this->upsertRetailPass('giftcard', $builder->buildGiftCardClass($card), $builder->buildGiftCardObject($card));

        return $this->buildResourceSaveUrl('giftCardObjects', $builder->giftCardObjectId($card));
    }

    public function updateGiftCard(GiftCardData $card, bool $notify = false): bool
    {
        $builder = $this->retailBuilder();
        $object = $builder->buildGiftCardObject($card);

        if ($card->expiresAt === null) {
            $object->validTimeInterval = Model::NULL_VALUE;
        }

        if ($card->pin === null) {
            $object->pin = Model::NULL_VALUE;
        }

        if ($card->balanceUpdatedAt === null) {
            $object->setBalanceUpdateTime(new WalletDateTime(['date' => now()->format(DATE_ATOM)]));
        }

        return $this->updateRetailPass('giftcard', $builder->buildGiftCardClass($card), $object, $notify);
    }

    public function createOffer(OfferData $offer): string
    {
        $builder = $this->retailBuilder();

        $this->upsertRetailPass('offer', $builder->buildOfferClass($offer), $builder->buildOfferObject($offer));

        return $this->buildResourceSaveUrl('offerObjects', $builder->offerObjectId($offer));
    }

    public function updateOffer(OfferData $offer, bool $notify = false): bool
    {
        $builder = $this->retailBuilder();
        $object = $builder->buildOfferObject($offer);
        $class = $builder->buildOfferClass($offer);

        if ($offer->validFrom === null && $offer->expiresAt === null) {
            $object->validTimeInterval = Model::NULL_VALUE;
        } else {
            $interval = $object->getValidTimeInterval();

            if ($offer->validFrom === null) {
                $interval->start = Model::NULL_VALUE;
            }

            if ($offer->expiresAt === null) {
                $interval->end = Model::NULL_VALUE;
            }
        }

        if ($offer->barcodeValue === null) {
            $object->barcode = Model::NULL_VALUE;
        }

        if ($offer->redeemOnlineUrl === null) {
            $object->linksModuleData = Model::NULL_VALUE;
        }

        foreach (['details' => $offer->details, 'finePrint' => $offer->terms, 'titleImage' => $class->getTitleImage()] as $field => $value) {
            if ($value === null) {
                $class->{$field} = Model::NULL_VALUE;
            }
        }

        return $this->updateRetailPass('offer', $class, $object, $notify);
    }

    public function sendMessage(string $passType, string $objectId, string $header, string $body, bool $notify = false): void
    {
        if (! in_array($passType, ['loyalty', 'giftcard', 'offer'], true)) {
            throw new InvalidArgumentException('Unsupported Google Wallet pass type.');
        }

        $this->validateObjectId($objectId);
        RetailPassValidation::required($header, 'header');
        RetailPassValidation::required($body, 'body');

        $this->getService()->{$passType.'object'}->addmessage($objectId, new AddMessageRequest([
            'message' => [
                'header' => $header,
                'body' => $body,
                'messageType' => $notify ? 'TEXT_AND_NOTIFY' : 'TEXT',
            ],
        ]));
    }

    protected function retailBuilder(): DefaultGoogleRetailPayloadBuilder
    {
        return $this->retailPayloadBuilder ??= new DefaultGoogleRetailPayloadBuilder($this->imageUrlResolver);
    }

    protected function updateRetailPass(string $type, Model $class, Model $object, bool $notify): bool
    {
        if ($notify) {
            $object->notifyPreference = 'NOTIFY_ON_UPDATE';
        }

        try {
            $this->upsertRetailPass($type, $class, $object, true);

            return true;
        } catch (GoogleServiceException $exception) {
            Log::warning('Google Wallet retail pass update failed.', ['type' => $type, 'status' => $exception->getCode()]);

            return false;
        }
    }

    protected function upsertRetailPass(string $type, Model $class, Model $object, bool $patchFirst = false): void
    {
        $service = $this->getService();
        $classResource = $service->{$type.'class'};
        $objectResource = $service->{$type.'object'};

        try {
            $classResource->insert($class);
        } catch (GoogleServiceException $exception) {
            if ((int) $exception->getCode() !== 409) {
                throw $exception;
            }

            $classResource->patch($class->getId(), $class);
        }

        if ($patchFirst) {
            try {
                $objectResource->patch($object->getId(), $object);

                return;
            } catch (GoogleServiceException $exception) {
                if ((int) $exception->getCode() !== 404) {
                    throw $exception;
                }
            }
        }

        try {
            $objectResource->insert($object);
        } catch (GoogleServiceException $exception) {
            if ((int) $exception->getCode() !== 409) {
                throw $exception;
            }

            $objectResource->patch($object->getId(), $object);
        }
    }

    protected function upsertLoyaltyClass(Walletobjects $service, LoyaltyProgramData $program): void
    {
        $loyaltyClass = $this->payloadBuilder->buildClass($program);

        try {
            $service->loyaltyclass->insert($loyaltyClass);
        } catch (GoogleServiceException $e) {
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

        return json_decode(json_encode($loyaltyObject->toSimpleObject(), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param  list<array<string, mixed>>  $objects
     */
    protected function buildSaveUrlWithObjects(array $objects): string
    {
        return $this->signSavePayload(['loyaltyObjects' => $objects]);
    }

    protected function buildSaveUrl(string $objectId): string
    {
        return $this->buildResourceSaveUrl('loyaltyObjects', $objectId);
    }

    protected function buildResourceSaveUrl(string $payloadKey, string $objectId): string
    {
        $this->validateObjectId($objectId);

        return $this->signSavePayload([$payloadKey => [['id' => $objectId]]]);
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $payload
     */
    protected function signSavePayload(array $payload): string
    {
        $credentials = $this->credentials();
        $issuedAt = now()->getTimestamp();
        $claims = [
            'iss' => $credentials['client_email'],
            'aud' => 'google',
            'typ' => 'savetowallet',
            'iat' => $issuedAt,
            'exp' => $issuedAt + 300,
            'origins' => array_values(config('google-wallet.origins', [])),
            'payload' => $payload,
        ];

        $jwt = JWT::encode($claims, $credentials['private_key'], 'RS256');

        return "https://pay.google.com/gp/v/save/{$jwt}";
    }

    protected function validateObjectId(string $objectId): void
    {
        $issuer = (string) config('google-wallet.issuer_id');

        if (! preg_match('/^[0-9]+$/D', $issuer)
            || ! preg_match('/^'.preg_quote($issuer, '/').'\.[A-Za-z0-9._-]+$/D', $objectId)) {
            throw new InvalidArgumentException('The object ID must belong to the configured Google Wallet issuer.');
        }
    }

    protected function credentialsPath(): string
    {
        return $this->resolvePath((string) config('google-wallet.service_account_json'));
    }

    protected function getClient(): Client
    {
        $client = new Client;
        $client->setAuthConfig($this->credentials());
        $client->addScope('https://www.googleapis.com/auth/wallet_object.issuer');
        $client->setHttpClient(new HttpClient([
            'connect_timeout' => max(1, (int) config('google-wallet.connect_timeout', 5)),
            'timeout' => max(1, (int) config('google-wallet.timeout', 20)),
        ]));

        return $client;
    }

    protected function getService(): Walletobjects
    {
        return new Walletobjects($this->getClient());
    }

    /**
     * @return array{type: string, client_email: string, private_key: string}
     */
    protected function credentials(): array
    {
        $path = $this->credentialsPath();

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Google Wallet service account credentials are unavailable.');
        }

        try {
            $credentials = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new RuntimeException('Google Wallet service account credentials are invalid.');
        }

        if (! is_array($credentials)
            || ($credentials['type'] ?? null) !== 'service_account'
            || ! is_string($credentials['client_email'] ?? null)
            || ! filter_var($credentials['client_email'], FILTER_VALIDATE_EMAIL)
            || ! is_string($credentials['private_key'] ?? null)) {
            throw new RuntimeException('Google Wallet service account credentials are invalid.');
        }

        $key = openssl_pkey_get_private($credentials['private_key']);
        $details = $key === false ? false : openssl_pkey_get_details($key);

        if ($details === false || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 2048) {
            throw new RuntimeException('Google Wallet requires an RSA private key of at least 2048 bits.');
        }

        return $credentials;
    }
}
