<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Google;

class WalletImageUrlResolver
{
    public function resolve(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $this->fallbackLogo();
        }

        $url = $this->rewriteWithPublicBase($url);

        if (!$this->isPubliclyAccessible($url)) {
            return $this->fallbackLogo();
        }

        if (!str_starts_with(strtolower($url), 'https://')) {
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

        if (!$this->isPubliclyAccessible($url)) {
            return null;
        }

        if (!str_starts_with(strtolower($url), 'https://')) {
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

            if ($this->isPubliclyAccessible($candidate) && str_starts_with(strtolower($candidate), 'https://')) {
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

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($url, $appUrl)) {
            return $publicBase . substr($url, strlen($appUrl));
        }

        $storagePath = '/storage/';
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';

        if ($path !== '' && str_contains($path, $storagePath)) {
            $suffix = substr($path, strpos($path, $storagePath));

            return $publicBase . $suffix;
        }

        return $url;
    }

    protected function isPubliclyAccessible(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            return false;
        }

        if (str_ends_with($host, '.test') || str_ends_with($host, '.local') || str_ends_with($host, '.invalid')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        }

        return true;
    }
}
