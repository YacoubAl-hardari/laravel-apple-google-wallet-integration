<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Yacoubalhaidari\AppleGoogleWallet\Google\WalletImageUrlResolver;

class WalletImageUrlResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://loyalty.test',
            'google-wallet.public_asset_base_url' => null,
            'google-wallet.fallback_logo' => null,
            'google-wallet.default_logo' => null,
        ]);
    }

    public function test_images_are_checked_with_bounded_head_requests_without_redirects(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request, array $options) {
            $this->assertSame('HEAD', $request->method());
            $this->assertSame('https://example.com/logo.png', $request->url());
            $this->assertFalse($options['allow_redirects']);
            $this->assertSame(3, $options['connect_timeout']);
            $this->assertSame(8, $options['timeout']);

            return Http::response('', 200, ['Content-Type' => 'image/png']);
        });

        $this->assertSame('https://example.com/logo.png', (new WalletImageUrlResolver)->resolveOptional('https://example.com/logo.png'));
        Http::assertSentCount(1);
    }

    #[DataProvider('unsafeUrls')]
    public function test_nonpublic_or_unsafe_urls_are_rejected_before_network_access(string $url): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $this->assertNull((new WalletImageUrlResolver)->resolveOptional($url));
        Http::assertNothingSent();
    }

    public static function unsafeUrls(): array
    {
        return array_map(fn (string $url): array => [$url], [
            'http://example.com/logo.png', 'https://localhost./logo.png', 'https://merchant.test./logo.png',
            'https://host.internal/logo.png', 'https://host.localhost/logo.png', 'https://host/logo.png',
            'https://127.0.0.1/logo.png', 'https://10.0.0.1/logo.png', 'https://[::1]/logo.png',
            'https://example.com:8443/logo.png', 'https://user:secret@example.com/logo.png',
        ]);
    }

    public function test_a_redirect_to_an_internal_address_is_not_followed(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://example.com/logo.png' => Http::response('', 302, ['Location' => 'https://127.0.0.1/private'])]);

        $this->assertNull((new WalletImageUrlResolver)->resolveOptional('https://example.com/logo.png'));
        Http::assertSentCount(1);
    }

    public function test_only_local_assets_are_rewritten_to_the_configured_public_base(): void
    {
        config(['google-wallet.public_asset_base_url' => 'https://cdn.example.com']);
        Http::preventStrayRequests();
        Http::fake([
            'https://cdn.example.com/storage/logo.png' => Http::response('', 200, ['Content-Type' => 'image/png']),
            'https://other.example.com/storage/logo.png' => Http::response('', 200, ['Content-Type' => 'image/png']),
            'https://loyalty.test.evil.example/storage/logo.png' => Http::response('', 200, ['Content-Type' => 'image/png']),
        ]);
        $resolver = new WalletImageUrlResolver;

        $this->assertSame('https://cdn.example.com/storage/logo.png', $resolver->resolveOptional('https://loyalty.test/storage/logo.png'));
        $this->assertSame('https://cdn.example.com/storage/logo.png', $resolver->resolveOptional('/storage/logo.png'));
        $this->assertSame('https://other.example.com/storage/logo.png', $resolver->resolveOptional('https://other.example.com/storage/logo.png'));
        $this->assertSame('https://loyalty.test.evil.example/storage/logo.png', $resolver->resolveOptional('https://loyalty.test.evil.example/storage/logo.png'));
        Http::assertSentCount(4);
    }

    public function test_html_and_unreachable_images_are_not_accepted_as_artwork(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://example.com/html' => Http::response('<html></html>', 200, ['Content-Type' => 'text/html']),
            'https://example.com/unreachable' => Http::failedConnection(),
        ]);
        $resolver = new WalletImageUrlResolver;

        $this->assertNull($resolver->resolveOptional('https://example.com/html'));
        $this->assertNull($resolver->resolveOptional('https://example.com/unreachable'));
        Http::assertSentCount(2);
    }
}
