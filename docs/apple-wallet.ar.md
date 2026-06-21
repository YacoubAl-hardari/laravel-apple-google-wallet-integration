# دليل Apple Wallet

دليل مختصر لإعداد واستخدام **Apple Wallet** مع مكتبة `yacoubalhaidari/laravel-apple-google-wallet-integration`.

| الملف                                      | المحتوى                               |
| ------------------------------------------ | ------------------------------------- |
| هذا الملف                                  | Apple Wallet — شهادات وإعداد واستخدام |
| [google-wallet.ar.md](google-wallet.ar.md) | دليل Google Wallet                    |
| [README.ar.md](README.ar.md)               | فهرس الوثائق بالعربية                 |

## الهدف

هذا الدليل يشرح بسرعة كيفية تجهيز شهادات Apple Wallet Passes لاستخدامها داخل Laravel.

- إنشاء **Pass Type ID** من Apple Developer
- إنشاء **CSR** و **Private Key** باستخدام OpenSSL
- تنزيل شهادة Apple وتحويلها من **CER** إلى **PEM**
- إنشاء ملف **P12** مع كلمة مرور
- تنزيل شهادة **Apple WWDR** وتحويلها إلى **PEM**

## المتطلبات

- حساب Apple Developer نشط
- صلاحية الوصول إلى Certificates, Identifiers & Profiles
- جهاز Windows مع PowerShell
- OpenSSL مثبت أو متاح عبر `winget`

> احفظ ملفات Private Key و P12 وكلمة المرور في مكان آمن.

## إنشاء Pass Type ID

1. افتح [Apple Developer](https://developer.apple.com)
2. انتقل إلى [Identifiers](https://developer.apple.com/account/resources/identifiers/list)
3. اختر **App IDs** ثم **Pass Type IDs**
4. أضف قيمة Identifier مثل:

```text
pass.com.example.yourapp
```

## إنشاء شهادة Pass Type ID

1. افتح [Certificates](https://developer.apple.com/account/resources/certificates/list)
2. اختر **Development** ثم **Pass Type ID Certificate**
3. ارفع ملف CSR لاحقًا من الخطوة التالية

## تثبيت OpenSSL

```powershell
winget install ShiningLight.OpenSSL.Light
openssl version
```

## توليد CSR و Private Key

```powershell
$openssl = (Get-Command openssl).Source
& $openssl req -new -newkey rsa:2048 -nodes -keyout YourDomainNameHere.test.key -out YourDomainNameHere.test.csr
```

## تنزيل الشهادة وتحويلها

بعد تنزيل `pass.cer`:

```powershell
& $openssl x509 -in pass.cer -inform DER -out pass.pem -outform PEM
& $openssl pkcs12 -export -out pass.p12 -inkey YourDomainNameHere.test.key -in pass.pem
```

## تنزيل WWDR

حمّل **Worldwide Developer Relations - G6** من صفحة Apple Certificate Authority ثم حوّله:

```powershell
& $openssl x509 -in AppleWWDRCAG6.cer -inform DER -out AppleWWDRCAG6.pem -outform PEM
```

## الملفات النهائية

| الملف                         | الاستخدام   |
| ----------------------------- | ----------- |
| `pass.p12`                    | شهادة الربط |
| `AppleWWDRCAG6.pem`           | شهادة WWDR  |
| `YourDomainNameHere.test.key` | Private Key |

## ربط الشهادات بالمكتبة

```env
APPLE_WALLET_PASS_TYPE_IDENTIFIER=pass.com.example.yourapp
APPLE_WALLET_TEAM_IDENTIFIER=XXXXXXXXXX
APPLE_WALLET_CERTIFICATE_PATH=storage/app/apple-wallet/pass.p12
APPLE_WALLET_CERTIFICATE_PASS=your-p12-password
APPLE_WALLET_WWDR_CERTIFICATE=storage/app/apple-wallet/AppleWWDRCAG6.pem
```

## الاستخدام السريع

```php
use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleWalletService;

$report = app(AppleWalletService::class)->configurationReport();
```

ارجع إلى [README.ar.md](README.ar.md) أو [README](../README.md) حسب اللغة التي تفضلها.
