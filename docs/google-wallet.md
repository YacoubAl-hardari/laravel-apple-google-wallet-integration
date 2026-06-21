# دليل Google Wallet

> **قريباً** — هذا الملف سيُكمَّل لاحقاً بشرح إعداد Google Wallet API خطوة بخطوة.

## الوثائق

| الملف | الحالة |
|-------|--------|
| [apple-wallet.md](apple-wallet.md) | جاهز — شهادات + إعداد + استخدام |
| **google-wallet.md** | قيد الإعداد |

---

## الإعداد المؤقت (`.env`)

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

---

ارجع إلى [README](../README.md) للتفاصيل الكاملة حتى نشر هذا الدليل.
