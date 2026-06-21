# Laravel Apple & Google Wallet Integration

مكتبة Laravel لتوليد بطاقات ولاء **Apple Wallet** (`.pkpass`) و **Google Wallet** مع دعم بطاقات الأختام (Stamp Cards).

**المطور:** [Yacoub Alhaidari](https://yacoubalhaidari.com)

---

## جدول المحتويات

- [المميزات](#المميزات)
- [المتطلبات](#المتطلبات)
- [التثبيت](#التثبيت)
- [الإعداد السريع](#الإعداد-السريع)
- [الوثائق](#الوثائق)
- [الترجمة ar / en](#الترجمة-ar--en)
- [Wallet Design Studio](#wallet-design-studio)
- [الاستخدام](#الاستخدام)
- [التوسعة](#التوسعة)
- [الترخيص](#الترخيص)

---

## المميزات

- توليد **Apple Wallet** `.pkpass` مع صور شريط الأختام
- **Google Wallet** Loyalty Class/Object مع روابط JWT
- مولّد صور الأختام (GD) للمنصتين
- **DTO-based API** — بدون ربط Eloquent
- Builders قابلة للاستبدال + Events
- ترجمة **عربي / إنجليزي**
- **Wallet Design Studio** — مصمم visual للإعدادات (local)

---

## المتطلبات

| المتطلب | التفاصيل |
|---------|----------|
| PHP | 8.2+ |
| Laravel | 11 أو 12 |
| Extensions | `openssl`, `zip`, `gd` |
| Apple | Pass Type ID + `.p12` + WWDR `.pem` |
| Google | Service Account JSON + Issuer ID |

---

## التثبيت

```bash
composer require yacoubalhaidari/laravel-apple-google-wallet-integration
```

```bash
php artisan vendor:publish --tag=apple-google-wallet-config
php artisan vendor:publish --tag=apple-google-wallet-lang   # اختياري
php artisan storage:link
```

---

## الإعداد السريع

| المنصة | الدليل | ملخص |
|--------|--------|------|
| **Apple** | [docs/apple-wallet.md](docs/apple-wallet.md) | شهادات → `storage/app/apple-wallet/` → `.env` |
| **Google** | [docs/google-wallet.md](docs/google-wallet.md) | Service Account → `storage/app/google-wallet/` → `.env` |

### `.env` — الحد الأدنى

```env
# Apple — التفاصيل في docs/apple-wallet.md
APPLE_WALLET_PASS_TYPE_IDENTIFIER=pass.com.example.yourapp
APPLE_WALLET_TEAM_IDENTIFIER=XXXXXXXXXX
APPLE_WALLET_CERTIFICATE_PATH=storage/app/apple-wallet/pass.p12
APPLE_WALLET_CERTIFICATE_PASS=your-p12-password
APPLE_WALLET_WWDR_CERTIFICATE=storage/app/apple-wallet/AppleWWDRCAG6.pem

# Google — التفاصيل في docs/google-wallet.md
GOOGLE_WALLET_ISSUER_ID=3388000000000000000
GOOGLE_WALLET_SERVICE_ACCOUNT_JSON=storage/app/google-wallet/service-account.json
GOOGLE_WALLET_FALLBACK_LOGO=https://cdn.example.com/logo.png

WALLET_LOCALE=ar
```

### التحقق

```php
app(\Yacoubalhaidari\AppleGoogleWallet\Apple\AppleWalletService::class)->configurationReport();
app(\Yacoubalhaidari\AppleGoogleWallet\Google\GoogleWalletService::class)->configurationReport();
```

---

## الوثائق

| الملف | المحتوى |
|-------|---------|
| **[docs/apple-wallet.md](docs/apple-wallet.md)** | دليل Apple Wallet الكامل (شهادات + إعداد + استخدام) |
| **[docs/google-wallet.md](docs/google-wallet.md)** | دليل Google Wallet *(قريباً)* |
| [docs/README.md](docs/README.md) | فهرس الوثائق |
| [config/apple-wallet.php](config/apple-wallet.php) | خيارات Apple |
| [config/google-wallet.php](config/google-wallet.php) | خيارات Google |

---

## الترجمة ar / en

```env
WALLET_LOCALE=ar
```

```php
wallet_trans('stamps');
```

```bash
php artisan vendor:publish --tag=apple-google-wallet-lang
```

---

## Wallet Design Studio

```
http://your-app.test/wallet-studio
```

```env
WALLET_STUDIO_ENABLED=true   # في production
```

---

## الاستخدام

### DTOs (مشترك بين Apple و Google)

```php
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;

$program = LoyaltyProgramData::fromArray($card->toArray());
$member = MemberCardData::fromArray([
    ...$userCard->toArray(),
    'member_name' => trim($user->first_name . ' ' . $user->last_name),
]);
```

### Apple Wallet

→ التفاصيل: **[docs/apple-wallet.md](docs/apple-wallet.md#الجزء-الثالث--الاستخدام)**

```php
use Yacoubalhaidari\AppleGoogleWallet\Facades\AppleWallet;

AppleWallet::createPass($program, $member);
```

### Google Wallet

→ التفاصيل: **[docs/google-wallet.md](docs/google-wallet.md)**

```php
use Yacoubalhaidari\AppleGoogleWallet\Facades\GoogleWallet;

GoogleWallet::saveUrl($program, $member);
GoogleWallet::updateLoyaltyCard($program, $member);
```

---

## التوسعة

```php
// config/apple-wallet.php
'pass_definition_builder' => App\Wallet\CustomApplePassBuilder::class,

// config/google-wallet.php
'payload_builder' => App\Wallet\CustomGooglePayloadBuilder::class,
```

Events: `ApplePassDefinitionBuilding`, `GoogleLoyaltyObjectBuilding`

---

## الترخيص

MIT — Yacoub Alhaidari
