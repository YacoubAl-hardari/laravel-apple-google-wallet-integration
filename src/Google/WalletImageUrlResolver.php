<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Google;

use Illuminate\Support\Facades\Http;
use Throwable;

class WalletImageUrlResolver
{
    public function resolve(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $this->fallbackLogo();
        }

        $url = $this->rewriteWithPublicBase($url);

        if (! $this->isPubliclyAccessible($url)) {
            return $this->fallbackLogo();
        }

        if (! str_starts_with(strtolower($url), 'https://')) {
            return $this->fallbackLogo();
        }

        if (! $this->isValidImageUrl($url)) {
            return $this->fallbackLogo();
        }

        return $url;
    }

    public function resolveOptional(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $url = $this->rewriteWithPublicBase($url);

        if (! $this->isPubliclyAccessible($url)) {
            return null;
        }

        if (! str_starts_with(strtolower($url), 'https://')) {
            return null;
        }

        if (! $this->isValidImageUrl($url)) {
            return null;
        }

        return $url;
    }

    protected function fallbackLogo(): ?string
    {
        $candidates = array_filter([
            config('google-wallet.fallback_logo'),
            config('google-wallet.default_logo'),
        ]);

        foreach ($candidates as $candidate) {
            $candidate = $this->rewriteWithPublicBase(trim((string) $candidate));

            if ($this->isPubliclyAccessible($candidate)
                && str_starts_with(strtolower($candidate), 'https://')
                && $this->isValidImageUrl($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function rewriteWithPublicBase(string $url): string
    {
        $publicBase = rtrim((string) config('google-wallet.public_asset_base_url'), '/');
        if ($publicBase === '') {
            return $url;
        }

        $parsed = parse_url($url);
        $app = parse_url(rtrim((string) config('app.url'), '/'));

        if ($parsed === false || $app === false) {
            return $url;
        }

        $sameOrigin = isset($parsed['host'], $app['host'])
            && strcasecmp($parsed['host'], $app['host']) === 0
            && ($parsed['scheme'] ?? '') === ($app['scheme'] ?? '')
            && ($parsed['port'] ?? 443) === ($app['port'] ?? 443);

        if ($sameOrigin || (! isset($parsed['host']) && str_starts_with($url, '/storage/'))) {
            $path = $parsed['path'] ?? '';
            $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';

            return $publicBase.$path.$query;
        }

        return $url;
    }

    protected function isPubliclyAccessible(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        $host = rtrim(strtolower(trim($parts['host'] ?? '', '[]')), '.');

        if (($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass'])
            || ($parts['port'] ?? 443) !== 443 || $host === '' || $host === 'localhost') {
            return false;
        }

        if (preg_match('/\.(?:test|local|localhost|internal|invalid|lan|home)$/D', $host)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        }

        return str_contains($host, '.');
    }

    protected function isValidImageUrl(string $url): bool
    {
        try {
            $response = Http::connectTimeout(3)->timeout(8)->withoutRedirecting()->head($url);
        } catch (Throwable) {
            return false;
        }

        return $response->status() === 200
            && str_starts_with(strtolower((string) $response->header('Content-Type')), 'image/');
    }
}
