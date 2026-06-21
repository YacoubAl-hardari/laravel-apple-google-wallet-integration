# دليل Google Wallet

دليل مختصر لإعداد **Google Wallet** مع `yacoubalhaidari/laravel-apple-google-wallet-integration`.

| الملف                                    | الحالة                       |
| ---------------------------------------- | ---------------------------- |
| [apple-wallet.ar.md](apple-wallet.ar.md) | دليل Apple Wallet بالعربية   |
| هذا الملف                                | إعداد واستخدام Google Wallet |

## إعداد `.env`

```env
GOOGLE_WALLET_ISSUER_ID=3388000000000000000
GOOGLE_WALLET_SERVICE_ACCOUNT_JSON=storage/app/google-wallet/service-account.json
GOOGLE_WALLET_ISSUER_NAME="My App"

GOOGLE_WALLET_HEX_BACKGROUND=#8B5E3C
GOOGLE_WALLET_DEFAULT_LOGO=https://cdn.example.com/logo.png
GOOGLE_WALLET_FALLBACK_LOGO=https://cdn.example.com/logo.png
GOOGLE_WALLET_PUBLIC_ASSET_BASE_URL=https://cdn.example.com
```

## ملفات المشروع

```text
storage/app/google-wallet/
└── service-account.json
```

## استخدام سريع

```php
use Yacoubalhaidari\AppleGoogleWallet\Google\GoogleWalletService;

$url = app(GoogleWalletService::class)->saveUrl($program, $member);
```

ارجع إلى [README.ar.md](README.ar.md) أو [README](../README.md) للاستخدام الكامل.
