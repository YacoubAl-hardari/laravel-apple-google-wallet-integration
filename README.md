# Laravel Apple & Google Wallet Integration

![Laravel Apple & Google Wallet Integration](image.png)

مكتبة Laravel لتوليد بطاقات ولاء **Apple Wallet** بصيغة `.pkpass` وروابط **Google Wallet**، مع دعم بطاقات الأختام وتوليد صور الأختام للمنصتين.

**المطور:** [Yacoub Alhaidari](https://yacoubalhaidari.com)

---

## جدول المحتويات

- [المميزات](#المميزات)
- [المتطلبات](#المتطلبات)
- [التثبيت](#التثبيت)
- [الإعداد السريع](#الإعداد-السريع)
- [Wallet Design Studio](#wallet-design-studio)
- [الاستخدام](#الاستخدام)
- [التوسعة](#التوسعة)
- [الوثائق](#الوثائق)
- [الترجمة](#الترجمة)
- [الترخيص](#الترخيص)

---

## المميزات

- توليد بطاقات **Apple Wallet** بصيغة `.pkpass`.
- إنشاء روابط حفظ **Google Wallet** باستخدام JWT.
- إنشاء أو تحديث Google Loyalty Class/Object.
- دعم بطاقات الأختام مع مولد صور مبني على GD.
- API مبني على DTOs بدون ربط مباشر مع Eloquent.
- Builders قابلة للاستبدال لتخصيص تعريفات Apple و Google.
- Events قبل بناء تعريفات البطاقات.
- ترجمة عربية وإنجليزية.
- **Wallet Design Studio** محلي لتجربة الإعدادات وتصديرها.

---

## المتطلبات

| المتطلب    | التفاصيل                                        |
| ---------- | ----------------------------------------------- |
| PHP        | 8.2 أو أحدث                                     |
| Laravel    | 11 أو 12                                        |
| Extensions | `openssl`, `zip`, `gd`                          |
| Apple      | Pass Type ID + شهادة `.p12` + شهادة WWDR `.pem` |
| Google     | Service Account JSON + Issuer ID                |

---

## التثبيت

```bash
composer require yacoubalhaidari/laravel-apple-google-wallet-integration
```

انشر ملفات الإعداد والترجمة عند الحاجة:

```bash
php artisan vendor:publish --tag=apple-google-wallet-config
php artisan vendor:publish --tag=apple-google-wallet-lang
php artisan storage:link
```

---

## الإعداد السريع

ضع شهادات Apple داخل `storage/app/apple-wallet/` وملف Google Service Account داخل `storage/app/google-wallet/`، ثم اضبط القيم الأساسية في `.env`:

```env
APP_URL=https://example.com
WALLET_LOCALE=ar

APPLE_WALLET_PASS_TYPE_IDENTIFIER=pass.com.example.yourapp
APPLE_WALLET_TEAM_IDENTIFIER=XXXXXXXXXX
APPLE_WALLET_ORGANIZATION_NAME="Your Brand"
APPLE_WALLET_CERTIFICATE_PATH=storage/app/apple-wallet/pass.p12
APPLE_WALLET_CERTIFICATE_PASS=your-p12-password
APPLE_WALLET_WWDR_CERTIFICATE=storage/app/apple-wallet/AppleWWDRCAG6.pem
APPLE_WALLET_ICON_PATH=public/images/logo.png
APPLE_WALLET_LOGO_PATH=public/images/logo.png

GOOGLE_WALLET_ISSUER_ID=3388000000000000000
GOOGLE_WALLET_SERVICE_ACCOUNT_JSON=storage/app/google-wallet/service-account.json
GOOGLE_WALLET_ISSUER_NAME="Your Brand"
GOOGLE_WALLET_DEFAULT_LOGO=https://example.com/images/logo.png
GOOGLE_WALLET_FALLBACK_LOGO=https://example.com/images/logo.png
GOOGLE_WALLET_PUBLIC_ASSET_BASE_URL=https://example.com
```

إعدادات الأختام الاختيارية:

```env
APPLE_WALLET_STAMP_COMPLETED_ICON=public/images/stamps/completed.png
APPLE_WALLET_STAMP_EMPTY_ICON=public/images/stamps/empty.png
APPLE_WALLET_STRIP_BG_IMAGE=/images/stamps/STAMP_BG.png

GOOGLE_WALLET_STAMP_COMPLETED_ICON=public/images/stamps/completed.png
GOOGLE_WALLET_STAMP_EMPTY_ICON=public/images/stamps/empty.png
GOOGLE_WALLET_STAMP_STRIP_BG_IMAGE=/images/stamps/STAMP_BG.jpeg
```

يمكنك فحص الإعدادات من Tinker:

```php
app(\Yacoubalhaidari\AppleGoogleWallet\Apple\AppleWalletService::class)->configurationReport();
app(\Yacoubalhaidari\AppleGoogleWallet\Google\GoogleWalletService::class)->configurationReport();
```

---

## Wallet Design Studio

الاستوديو يعمل تلقائيًا في بيئة `local`، ويمكن فتحه من:

```text
http://your-app.test/wallet-studio
```

يمكن تغيير المسار أو التفعيل من `.env`:

```env
WALLET_STUDIO_ENABLED=true
WALLET_STUDIO_ROUTE=wallet-studio
```

يفيد الاستوديو في معاينة ألوان وتصميم البطاقة، رفع الأيقونات، توليد Preview، تصدير إعدادات `.env`، وتجربة إنشاء بطاقة اختبار.

---

## الاستخدام

### تجهيز DTOs

```php
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;

$program = LoyaltyProgramData::fromArray([
    'id' => $card->id,
    'name' => $card->name,
    'required_stamps' => $card->required_stamps,
    'reward_count' => $card->reward_count,
    'logo_path' => public_path('images/logo.png'),
    'image_url' => asset('images/logo.png'),
]);

$member = MemberCardData::fromArray([
    'id' => $userCard->id,
    'qr_code' => $userCard->qr_code,
    'stamps_progress' => $userCard->stamps_progress,
    'rewards_earned' => $userCard->rewards_earned,
    'is_completed' => $userCard->is_completed,
    'member_name' => trim($user->first_name . ' ' . $user->last_name),
]);
```

### Apple Wallet

```php
use Yacoubalhaidari\AppleGoogleWallet\Facades\AppleWallet;

$pkpass = AppleWallet::createPass($program, $member);

return response($pkpass, 200, [
    'Content-Type' => 'application/vnd.apple.pkpass',
    'Content-Disposition' => 'attachment; filename="loyalty.pkpass"',
]);
```

### Google Wallet

```php
use Yacoubalhaidari\AppleGoogleWallet\Facades\GoogleWallet;

$saveUrl = GoogleWallet::saveUrl($program, $member);

GoogleWallet::updateLoyaltyCard($program, $member);
```

---

## التوسعة

يمكن استبدال Builders الافتراضية من ملفات الإعداد:

```php
// config/apple-wallet.php
'pass_definition_builder' => App\Wallet\CustomApplePassBuilder::class,

// config/google-wallet.php
'payload_builder' => App\Wallet\CustomGooglePayloadBuilder::class,
```

الأحداث المتاحة:

```php
Yacoubalhaidari\AppleGoogleWallet\Events\ApplePassDefinitionBuilding::class
Yacoubalhaidari\AppleGoogleWallet\Events\GoogleLoyaltyObjectBuilding::class
```

---

## الوثائق

| الملف                                                | المحتوى                                 |
| ---------------------------------------------------- | --------------------------------------- |
| [docs/apple-wallet.md](docs/apple-wallet.md)         | إعداد Apple Wallet والشهادات والاستخدام |
| [docs/google-wallet.md](docs/google-wallet.md)       | إعداد Google Wallet و Service Account   |
| [docs/README.md](docs/README.md)                     | فهرس الوثائق                            |
| [config/apple-wallet.php](config/apple-wallet.php)   | خيارات Apple Wallet                     |
| [config/google-wallet.php](config/google-wallet.php) | خيارات Google Wallet                    |
| [config/studio.php](config/studio.php)               | خيارات Wallet Design Studio             |

---

## الترجمة

القيمة الافتراضية يمكن ضبطها من:

```env
WALLET_LOCALE=ar
```

واستخدام الترجمة:

```php
wallet_trans('stamps');
```

لنشر ملفات الترجمة:

```bash
php artisan vendor:publish --tag=apple-google-wallet-lang
```

---

## الترخيص

MIT - Yacoub Alhaidari
