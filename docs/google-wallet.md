# Google Wallet Guide

This file gives a quick setup path for **Google Wallet** with `yacoubalhaidari/laravel-apple-google-wallet-integration`.

| File                               | Status                                              |
| ---------------------------------- | --------------------------------------------------- |
| [apple-wallet.md](apple-wallet.md) | Ready - Apple Wallet certificates, setup, and usage |
| This file                          | Google Wallet setup and usage                       |

## Quick `.env` setup

```env
GOOGLE_WALLET_ISSUER_ID=3388000000000000000
GOOGLE_WALLET_SERVICE_ACCOUNT_JSON=storage/app/google-wallet/service-account.json
GOOGLE_WALLET_ISSUER_NAME="My App"

GOOGLE_WALLET_HEX_BACKGROUND=#8B5E3C
GOOGLE_WALLET_DEFAULT_LOGO=https://cdn.example.com/logo.png
GOOGLE_WALLET_FALLBACK_LOGO=https://cdn.example.com/logo.png
GOOGLE_WALLET_PUBLIC_ASSET_BASE_URL=https://cdn.example.com
```

## Project files

```text
storage/app/google-wallet/
└── service-account.json
```

## Quick usage

```php
use Yacoubalhaidari\AppleGoogleWallet\Google\GoogleWalletService;

$url = app(GoogleWalletService::class)->saveUrl($program, $member);
```

Return to [README](../README.md) for the full package overview.
