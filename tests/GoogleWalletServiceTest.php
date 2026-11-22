<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Tests;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use Google\Client;
use Google\Service\Exception as GoogleServiceException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\GiftCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\OfferData;
use Yacoubalhaidari\AppleGoogleWallet\Google\DefaultGoogleLoyaltyPayloadBuilder;
use Yacoubalhaidari\AppleGoogleWallet\Google\DefaultGoogleRetailPayloadBuilder;
use Yacoubalhaidari\AppleGoogleWallet\Google\GoogleWalletService;
use Yacoubalhaidari\AppleGoogleWallet\Google\StampCardImageGenerator;
use Yacoubalhaidari\AppleGoogleWallet\Google\WalletImageUrlResolver;

class GoogleWalletServiceTest extends TestCase
{
    private static string $privateKey = '';

    private static string $publicKey;

    private string $credentialsFile;

    /**
     * @var list<array{request: RequestInterface, response: ResponseInterface|null, error: mixed, options: array<string, mixed>}>
     */
    private array $history = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $opensslConfig = tempnam(sys_get_temp_dir(), 'wallet-openssl-');
        file_put_contents($opensslConfig, "[req]\ndistinguished_name=req_dn\n[req_dn]\n");
        $options = ['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048, 'config' => $opensslConfig];

        try {
            $key = openssl_pkey_new($options);

            if ($key === false || ! openssl_pkey_export($key, self::$privateKey, null, $options)) {
                throw new \RuntimeException('The wallet tests require OpenSSL RSA key generation.');
            }

            self::$publicKey = openssl_pkey_get_details($key)['key'];
        } finally {
            unlink($opensslConfig);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentialsFile = tempnam(sys_get_temp_dir(), 'wallet-account-');
        file_put_contents($this->credentialsFile, json_encode([
            'type' => 'service_account',
            'client_email' => 'wallet-tests@example.iam.gserviceaccount.com',
            'private_key' => self::$privateKey,
        ], JSON_THROW_ON_ERROR));

        config([
            'google-wallet.issuer_id' => '123456',
            'google-wallet.issuer_name' => 'Coffee Club',
            'google-wallet.service_account_json' => $this->credentialsFile,
            'google-wallet.origins' => ['https://loyalty.example.com'],
            'google-wallet.card_layout' => 'points',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->credentialsFile) && is_file($this->credentialsFile)) {
            unlink($this->credentialsFile);
        }

        parent::tearDown();
    }

    #[DataProvider('retailResources')]
    public function test_creates_the_correct_retail_resources_and_a_signed_save_link(string $kind, string $resource, string $payloadKey): void
    {
        $this->travelTo(new DateTimeImmutable('2026-11-22T09:00:00+03:00'));
        $service = $this->service([$this->response(), $this->response()]);

        $url = $this->createRetailPass($service, $kind);

        $this->assertSame([
            ['POST', '/walletobjects/v1/'.$resource.'Class'],
            ['POST', '/walletobjects/v1/'.$resource.'Object'],
        ], $this->requestSummary());
        $object = $this->body(1);
        $claims = $this->verifiedClaims($url);
        $this->assertSame('wallet-tests@example.iam.gserviceaccount.com', $claims['iss']);
        $this->assertSame('google', $claims['aud']);
        $this->assertSame('savetowallet', $claims['typ']);
        $this->assertSame(1795327200, $claims['iat']);
        $this->assertSame(1795327500, $claims['exp']);
        $this->assertSame(['https://loyalty.example.com'], $claims['origins']);
        $this->assertSame([$payloadKey => [['id' => $object['id']]]], $claims['payload']);
        $this->assertSame($this->body(0)['id'], $object['classId']);
        $this->assertArrayNotHasKey('hasUsers', $object);
        $this->assertArrayNotHasKey('classReference', $object);
    }

    #[DataProvider('retailResources')]
    public function test_patches_existing_classes_and_objects_after_insert_conflicts(string $kind, string $resource, string $payloadKey): void
    {
        $service = $this->service([
            $this->response(409), $this->response(), $this->response(409), $this->response(),
        ]);

        $url = $this->createRetailPass($service, $kind);

        $classId = $this->body(0)['id'];
        $objectId = $this->body(2)['id'];
        $this->assertSame([
            ['POST', '/walletobjects/v1/'.$resource.'Class'],
            ['PATCH', '/walletobjects/v1/'.$resource.'Class/'.$classId],
            ['POST', '/walletobjects/v1/'.$resource.'Object'],
            ['PATCH', '/walletobjects/v1/'.$resource.'Object/'.$objectId],
        ], $this->requestSummary());
        $this->assertSame($this->body(0), $this->body(1));
        $this->assertSame($this->body(2), $this->body(3));
        $this->assertSame([$payloadKey => [['id' => $objectId]]], $this->verifiedClaims($url)['payload']);
    }

    #[DataProvider('retailResources')]
    public function test_updates_retail_objects_with_partial_patch_and_explicit_notification(string $kind, string $resource, string $payloadKey): void
    {
        $service = $this->service([$this->response(), $this->response(200, ['hasUsers' => true])]);

        $updated = $this->updateRetailPass($service, $kind, true);

        $this->assertTrue($updated);
        $body = $this->body(1);
        $this->assertSame([
            ['POST', '/walletobjects/v1/'.$resource.'Class'],
            ['PATCH', '/walletobjects/v1/'.$resource.'Object/'.$body['id']],
        ], $this->requestSummary());
        $this->assertSame('NOTIFY_ON_UPDATE', $body['notifyPreference']);
        $this->assertArrayNotHasKey('hasUsers', $body);
        $this->assertArrayNotHasKey('classReference', $body);
        $this->assertArrayNotHasKey('messages', $body);
    }

    #[DataProvider('retailResources')]
    public function test_recovers_when_an_object_is_created_concurrently_after_a_patch_404(string $kind, string $resource, string $payloadKey): void
    {
        $service = $this->service([
            $this->response(), $this->response(404), $this->response(409), $this->response(),
        ]);

        $updated = $this->updateRetailPass($service, $kind);

        $this->assertTrue($updated);
        $objectId = $this->body(1)['id'];
        $this->assertSame([
            ['POST', '/walletobjects/v1/'.$resource.'Class'],
            ['PATCH', '/walletobjects/v1/'.$resource.'Object/'.$objectId],
            ['POST', '/walletobjects/v1/'.$resource.'Object'],
            ['PATCH', '/walletobjects/v1/'.$resource.'Object/'.$objectId],
        ], $this->requestSummary());
        $this->assertArrayNotHasKey('notifyPreference', $this->body(3));
    }

    #[DataProvider('retailResources')]
    public function test_reports_provider_update_failures_without_attempting_to_create_an_object(string $kind, string $resource, string $payloadKey): void
    {
        $service = $this->service([$this->response(), $this->response(500)]);

        $updated = $this->updateRetailPass($service, $kind);

        $this->assertFalse($updated);
        $this->assertSame(['POST', 'PATCH'], array_column($this->requestSummary(), 0));
    }

    #[DataProvider('retailResources')]
    public function test_does_not_hide_a_permission_failure_when_creating_a_pass(string $kind, string $resource, string $payloadKey): void
    {
        $service = $this->service([$this->response(403)]);
        $this->expectException(GoogleServiceException::class);
        $this->expectExceptionCode(403);

        try {
            $this->createRetailPass($service, $kind);
        } finally {
            $this->assertSame([['POST', '/walletobjects/v1/'.$resource.'Class']], $this->requestSummary());
        }
    }

    public function test_clears_removed_gift_card_pin_and_expiry_during_an_update(): void
    {
        $service = $this->service([$this->response(), $this->response()]);

        $updated = $service->updateGiftCard($this->giftCard());

        $this->assertTrue($updated);
        $this->assertArrayHasKey('pin', $this->body(1));
        $this->assertNull($this->body(1)['pin']);
        $this->assertArrayHasKey('validTimeInterval', $this->body(1));
        $this->assertNull($this->body(1)['validTimeInterval']);
    }

    public function test_clears_removed_offer_barcode_validity_and_redemption_link_during_an_update(): void
    {
        $service = $this->service([$this->response(), $this->response()]);

        $updated = $service->updateOffer($this->offer());

        $this->assertTrue($updated);
        $body = $this->body(1);
        foreach (['barcode', 'validTimeInterval', 'linksModuleData'] as $field) {
            $this->assertArrayHasKey($field, $body);
            $this->assertNull($body[$field]);
        }
    }

    #[DataProvider('oneSidedOfferIntervals')]
    public function test_clears_the_removed_bound_of_an_offer_validity_interval(array $attributes, array $expectedInterval): void
    {
        $service = $this->service([$this->response(), $this->response()]);
        $offer = OfferData::fromArray([
            'id' => 'offer-42', 'program_id' => 'program-10', 'title' => 'Free coffee', 'provider' => 'Coffee Club',
            ...$attributes,
        ]);

        $updated = $service->updateOffer($offer);

        $this->assertTrue($updated);
        $this->assertEquals($expectedInterval, $this->body(1)['validTimeInterval']);
    }

    public function test_clears_removed_offer_terms_details_and_title_image_on_the_class(): void
    {
        $service = $this->service([$this->response(409), $this->response(), $this->response()], null);

        $updated = $service->updateOffer($this->offer());

        $this->assertTrue($updated);
        $this->assertSame('PATCH', $this->history[1]['request']->getMethod());
        $body = $this->body(1);
        foreach (['details', 'finePrint', 'titleImage'] as $field) {
            $this->assertArrayHasKey($field, $body);
            $this->assertNull($body[$field]);
        }
    }

    public function test_advances_the_balance_update_time_when_a_gift_card_is_updated(): void
    {
        $this->travelTo(new DateTimeImmutable('2026-11-22T06:00:00+00:00'));
        $service = $this->service([$this->response(), $this->response()]);

        $updated = $service->updateGiftCard($this->giftCard());

        $this->assertTrue($updated);
        $this->assertSame(['date' => '2026-11-22T06:00:00+00:00'], $this->body(1)['balanceUpdateTime']);
    }

    public function test_preserves_an_explicit_balance_update_time(): void
    {
        $service = $this->service([$this->response(), $this->response()]);
        $card = GiftCardData::fromArray([
            'id' => 'gift-42', 'program_id' => 'program-10', 'program_name' => 'Coffee Club', 'card_number' => '1234-5678',
            'balance_micros' => '25000000', 'currency_code' => 'SAR', 'balance_updated_at' => '2026-11-21T09:00:00+03:00',
        ]);

        $updated = $service->updateGiftCard($card);

        $this->assertTrue($updated);
        $this->assertSame(['date' => '2026-11-21T09:00:00+03:00'], $this->body(1)['balanceUpdateTime']);
    }

    #[DataProvider('messageResources')]
    public function test_routes_messages_to_the_selected_pass_type_with_the_requested_notification(string $kind, string $resource, bool $notify, string $messageType): void
    {
        $service = $this->service([$this->response()]);

        $service->sendMessage($kind, '123456.pass_42', 'Your reward', 'A reward is ready to redeem.', $notify);

        $this->assertSame([['POST', '/walletobjects/v1/'.$resource.'/123456.pass_42/addMessage']], $this->requestSummary());
        $this->assertEquals(['message' => [
            'header' => 'Your reward',
            'body' => 'A reward is ready to redeem.',
            'messageType' => $messageType,
        ]], $this->body(0));
    }

    #[DataProvider('invalidMessages')]
    public function test_rejects_invalid_messages_before_contacting_google(string $kind, string $objectId, string $header, string $body): void
    {
        $service = $this->service([]);
        $this->expectException(InvalidArgumentException::class);

        try {
            $service->sendMessage($kind, $objectId, $header, $body);
        } finally {
            $this->assertSame([], $this->history);
        }
    }

    public function test_updates_loyalty_without_fetching_and_resending_provider_owned_fields(): void
    {
        $service = $this->service([$this->response(409), $this->response(), $this->response(200, ['hasUsers' => true])]);
        $program = new LoyaltyProgramData('10', 'Coffee Club', 5, 1, merchantLocations: [['latitude' => 12.7855, 'longitude' => 45.0187]]);
        $member = new MemberCardData(42, 'member-42-qr', 3, 1, false, 'Ali');

        $updated = $service->updateLoyaltyCard($program, $member);

        $this->assertTrue($updated);
        $this->assertSame([
            ['POST', '/walletobjects/v1/loyaltyClass'],
            ['PATCH', '/walletobjects/v1/loyaltyClass/123456.stamp_Coffee_Club'],
            ['PATCH', '/walletobjects/v1/loyaltyObject/123456.member_42'],
        ], $this->requestSummary());
        $this->assertSame([['latitude' => 12.7855, 'longitude' => 45.0187]], $this->body(1)['merchantLocations']);
        $body = $this->body(2);
        $this->assertSame(['string' => '3 / 5'], $body['loyaltyPoints']['balance']);
        $this->assertArrayNotHasKey('hasUsers', $body);
        $this->assertArrayNotHasKey('classReference', $body);
        $this->assertArrayNotHasKey('linkedOfferIds', $body);
        $this->assertArrayNotHasKey('merchantLocations', $body);
    }

    public function test_includes_required_claims_and_only_writable_object_fields_in_inline_loyalty_save_links(): void
    {
        $this->travelTo(new DateTimeImmutable('2026-11-22T09:00:00+03:00'));
        $service = $this->service([$this->response()]);

        $url = $service->saveUrl(new LoyaltyProgramData('10', 'Coffee Club', 5, 1), new MemberCardData(42, 'member-42-qr', 3, 1, false, 'Ali'));

        $this->assertIsString($url);
        $claims = $this->verifiedClaims($url);
        $this->assertSame(1795327200, $claims['iat']);
        $this->assertSame(['https://loyalty.example.com'], $claims['origins']);
        $object = $claims['payload']['loyaltyObjects'][0];
        $this->assertSame('123456.member_42', $object['id']);
        $this->assertSame('ACTIVE', $object['state']);
        $this->assertSame('QR_CODE', $object['barcode']['type']);
        $this->assertArrayNotHasKey('hasUsers', $object);
        $this->assertArrayNotHasKey('classReference', $object);
        $this->assertCount(1, $this->history);
    }

    #[DataProvider('invalidCredentials')]
    public function test_rejects_readable_but_invalid_service_account_credentials(string $kind): void
    {
        $credentials = [
            'type' => 'service_account',
            'client_email' => 'wallet-tests@example.iam.gserviceaccount.com',
            'private_key' => self::$privateKey,
        ];

        if ($kind === 'invalid_json') {
            file_put_contents($this->credentialsFile, '{');
        } else {
            $credentials[$kind] = 'invalid';
            file_put_contents($this->credentialsFile, json_encode($credentials, JSON_THROW_ON_ERROR));
        }

        $service = $this->service([]);
        $this->assertFalse($service->isConfigured());
        $this->assertFalse($service->configurationReport()['service_account_json']['ok']);
        $this->assertSame([], $this->history);
    }

    public static function retailResources(): array
    {
        return [
            'gift card' => ['giftcard', 'giftCard', 'giftCardObjects'],
            'offer' => ['offer', 'offer', 'offerObjects'],
        ];
    }

    public static function messageResources(): array
    {
        return [
            'loyalty notification' => ['loyalty', 'loyaltyObject', true, 'TEXT_AND_NOTIFY'],
            'gift card text only' => ['giftcard', 'giftCardObject', false, 'TEXT'],
            'offer notification' => ['offer', 'offerObject', true, 'TEXT_AND_NOTIFY'],
        ];
    }

    public static function invalidMessages(): array
    {
        return [
            'unsupported pass type' => ['generic', '123456.pass_42', 'Header', 'Body'],
            'cross issuer object' => ['loyalty', '654321.pass_42', 'Header', 'Body'],
            'malformed identifier' => ['giftcard', '123456.pass/42', 'Header', 'Body'],
            'blank header' => ['offer', '123456.pass_42', ' ', 'Body'],
            'blank body' => ['offer', '123456.pass_42', 'Header', ' '],
        ];
    }

    public static function invalidCredentials(): array
    {
        return [
            'malformed JSON' => ['invalid_json'],
            'wrong credential type' => ['type'],
            'invalid account email' => ['client_email'],
            'invalid private key' => ['private_key'],
        ];
    }

    public static function oneSidedOfferIntervals(): array
    {
        return [
            'removed start' => [
                ['expires_at' => '2026-12-22T09:00:00+03:00'],
                ['end' => ['date' => '2026-12-22T09:00:00+03:00'], 'start' => null],
            ],
            'removed end' => [
                ['valid_from' => '2026-11-22T09:00:00+03:00'],
                ['end' => null, 'start' => ['date' => '2026-11-22T09:00:00+03:00']],
            ],
        ];
    }

    /**
     * @param  list<ResponseInterface>  $responses
     */
    private function service(array $responses, ?string $logoUrl = 'https://assets.example.com/logo.png'): GoogleWalletService
    {
        $this->history = [];
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($this->history));
        $client = new Client(['retry' => ['retries' => 0]]);
        $client->setAccessToken(['access_token' => 'synthetic-wallet-test-token', 'created' => time(), 'expires_in' => 3600]);
        $client->setHttpClient(new HttpClient(['handler' => $handler, 'http_errors' => false]));
        $resolver = $this->createStub(WalletImageUrlResolver::class);
        $resolver->method('resolve')->willReturn($logoUrl);
        $service = new class(new StampCardImageGenerator, $resolver, new DefaultGoogleLoyaltyPayloadBuilder($resolver), new DefaultGoogleRetailPayloadBuilder($resolver)) extends GoogleWalletService
        {
            public Client $testClient;

            protected function getClient(): Client
            {
                return $this->testClient;
            }
        };
        $service->testClient = $client;

        return $service;
    }

    private function giftCard(): GiftCardData
    {
        return new GiftCardData('gift-42', 'program-10', 'Coffee Club', '1234-5678', '25000000', 'SAR');
    }

    private function offer(): OfferData
    {
        return new OfferData('offer-42', 'program-10', 'Free coffee', 'Coffee Club');
    }

    private function createRetailPass(GoogleWalletService $service, string $kind): string
    {
        return $kind === 'giftcard' ? $service->createGiftCard($this->giftCard()) : $service->createOffer($this->offer());
    }

    private function updateRetailPass(GoogleWalletService $service, string $kind, bool $notify = false): bool
    {
        return $kind === 'giftcard' ? $service->updateGiftCard($this->giftCard(), $notify) : $service->updateOffer($this->offer(), $notify);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function response(int $status = 200, array $body = []): ResponseInterface
    {
        if ($status >= 400) {
            $body = ['error' => ['code' => $status, 'message' => 'Synthetic provider error']];
        }

        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body === [] ? (object) [] : $body, JSON_THROW_ON_ERROR));
    }

    /**
     * @return list<array{string, string}>
     */
    private function requestSummary(): array
    {
        return array_map(function (array $entry): array {
            $this->assertSame('walletobjects.googleapis.com', $entry['request']->getUri()->getHost());

            return [$entry['request']->getMethod(), $entry['request']->getUri()->getPath()];
        }, $this->history);
    }

    /**
     * @return array<string, mixed>
     */
    private function body(int $requestIndex): array
    {
        return json_decode((string) $this->history[$requestIndex]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function verifiedClaims(string $url): array
    {
        $prefix = 'https://pay.google.com/gp/v/save/';
        $this->assertStringStartsWith($prefix, $url);
        [$header, $payload, $signature] = explode('.', substr($url, strlen($prefix)));
        $this->assertSame('RS256', json_decode(JWT::urlsafeB64Decode($header), true, flags: JSON_THROW_ON_ERROR)['alg']);
        $this->assertSame(1, openssl_verify($header.'.'.$payload, JWT::urlsafeB64Decode($signature), self::$publicKey, OPENSSL_ALGO_SHA256));

        return json_decode(JWT::urlsafeB64Decode($payload), true, flags: JSON_THROW_ON_ERROR);
    }
}
