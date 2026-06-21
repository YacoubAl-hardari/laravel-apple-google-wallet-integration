# دليل Apple Wallet

دليل مستقل لإعداد واستخدام **Apple Wallet** مع مكتبة `yacoubalhaidari/laravel-apple-google-wallet-integration`.

| الدليل | المحتوى |
|--------|---------|
| **هذا الملف** | Apple Wallet — شهادات + إعداد + استخدام |
| [google-wallet.md](google-wallet.md) | Google Wallet *(قريباً)* |
| [README](../README.md) | نظرة عامة على المكتبة |

---

# الجزء الأول — الشهادات

**Pass Type ID Certificate + CSR + P12 + WWDR**

شرح عملي مرتب لإنشاء Pass Type ID داخل Apple Developer، ثم توليد CSR على Windows باستخدام OpenSSL، ثم تحويل ملفات الشهادة إلى الصيغ المطلوبة للربط داخل المكتبة.

> **ملاحظة:** تم إبقاء المصطلحات التقنية الأساسية بالإنجليزية (CSR، OpenSSL، Pass Type ID، Certificate، PEM، P12) حتى تكون مطابقة لما يظهر داخل Apple Developer و PowerShell.

---

## جدول المحتويات

1. [الهدف من الدليل](#1-الهدف-من-هذا-الدليل)
2. [المتطلبات](#2-المتطلبات-قبل-البدء)
3. [إنشاء Pass Type ID](#3-إنشاء-pass-type-id-داخل-apple-developer)
4. [إنشاء Pass Type ID Certificate](#4-إنشاء-pass-type-id-certificate)
5. [تثبيت OpenSSL](#5-تثبيت-openssl-على-windows)
6. [تجهيز مجلد العمل](#6-تجهيز-مجلد-العمل)
7. [تحديد مسار OpenSSL](#7-تحديد-مسار-openssl-داخل-powershell)
8. [توليد CSR و Private Key](#8-توليد-ملف-csr-وprivate-key)
9. [رفع CSR وتنزيل الشهادة](#9-رفع-csr-وتنزيل-شهادة-apple)
10. [تحويل pass.cer إلى PEM](#10-تحويل-passcer-إلى-passpem)
11. [إنشاء ملف P12](#11-إنشاء-ملف-p12)
12. [تنزيل Apple WWDR](#12-تنزيل-apple-wwdr-certificate)
13. [تحويل WWDR إلى PEM](#13-تحويل-applewwdrcag6cer-إلى-pem)
14. [الملفات النهائية](#14-الملفات-النهائية-المطلوبة)
15. [ربط الشهادات بالمكتبة](#15-ربط-الشهادات-بالمكتبة)
16. [ملخص الأوامر](#16-ملخص-الأوامر-كاملة)
17. [مشاكل شائعة](#17-مشاكل-شائعة-وحلول-سريعة)
18. [Checklist](#18-checklist-قبل-استخدام-الشهادة-في-المشروع)
19. [متغيرات `.env` الكاملة](#19-متغيرات-env-الكاملة)
20. [ملف الإعداد](#20-ملف-الإعداد)
21. [ترتيب الحقول](#21-ترتيب-الحقول-اختياري)
22. [توليد `.pkpass`](#22-توليد-pkpass)
23. [Facade](#23-facade)

---

## 1. الهدف من هذا الدليل

هذا الدليل يشرح طريقة تجهيز شهادات Apple Wallet Passes خطوة بخطوة. في النهاية سيكون لديك الملفات المطلوبة لاستخدامها داخل مشروع Laravel مع مكتبة `yacoubalhaidari/laravel-apple-google-wallet-integration`.

- إنشاء **Pass Type ID** من حساب Apple Developer
- إنشاء **Pass Type ID Certificate** من Apple Developer
- توليد ملف **CSR** وملف **Private Key** باستخدام OpenSSL
- تحويل شهادة Apple من **CER** إلى **PEM**
- تصدير ملف **P12** مع كلمة مرور خاصة
- تنزيل شهادة **Apple WWDR** وتحويلها إلى **PEM**

---

## 2. المتطلبات قبل البدء

- حساب نشط في [Apple Developer](https://developer.apple.com)
- صلاحية الوصول إلى **Certificates, Identifiers & Profiles**
- جهاز Windows عليه **PowerShell**
- تثبيت **OpenSSL** (أو عبر `winget`)
- اسم دومين واضح لاستخدامه داخل Identifier

> **تنبيه مهم:** احفظ ملفات **Private Key** و **P12** وكلمة مرور P12 في مكان آمن. لا ترسلها في محادثات عامة ولا ترفعها إلى GitHub.

---

## 3. إنشاء Pass Type ID داخل Apple Developer

### 3.1 تسجيل الدخول

افتح: [https://developer.apple.com](https://developer.apple.com)

### 3.2 فتح صفحة Identifiers

[https://developer.apple.com/account/resources/identifiers/list](https://developer.apple.com/account/resources/identifiers/list)

### 3.3 إنشاء Identifier جديد

- اضغط **Register App ID** إن ظهر، أو:
- اختر **App IDs** → **Pass Type IDs** → **Continue**

### 3.4 تعبئة البيانات

| الحقل | القيمة |
|-------|--------|
| Description | اسم واضح للمشروع |
| Identifier | `pass.com.example.YourDomainNameHere` |

**مثال:** `pass.com.example.yacoubalhaidari`

### 3.5 الحفظ

راجع البيانات ثم اضغط **Save** أو **Register**.

---

## 4. إنشاء Pass Type ID Certificate

### 4.1 فتح Certificates

[https://developer.apple.com/account/resources/certificates/list](https://developer.apple.com/account/resources/certificates/list)

### 4.2 إنشاء شهادة جديدة

1. من **All Types** اختر **Development**
2. اضغط **+** بجانب Certificates
3. اختر **Pass Type ID Certificate** → **Continue**
4. اكتب اسمًا للشهادة واختر Pass Type ID الذي أنشأته
5. ارفع ملف **CSR** (يُولَّد في الخطوة 8)
6. **Continue** → **Download**

---

## 5. تثبيت OpenSSL على Windows

افتح PowerShell **كمسؤول**:

```powershell
winget install ShiningLight.OpenSSL.Light
```

أغلق PowerShell وافتحه من جديد، ثم:

```powershell
openssl version
```

---

## 6. تجهيز مجلد العمل

```powershell
mkdir C:\apple-certs -Force
cd C:\apple-certs
```

---

## 7. تحديد مسار OpenSSL داخل PowerShell

```powershell
$openssl = (Get-Command openssl).Source
& $openssl version
```

إذا لم يعمل، جرّب المسار الكامل:

```powershell
$openssl = "C:\Program Files\OpenSSL-Win64\bin\openssl.exe"
& $openssl version
```

```powershell
$openssl = "C:\Program Files (x86)\OpenSSL-Win32\bin\openssl.exe"
& $openssl version
```

> **تنبيه:** إذا ظهر خطأ `The expression after '&' ... produced an object that was not valid`، فالمتغير `$openssl` فارغ. عرّفه أولًا ثم أعد المحاولة.

---

## 8. توليد ملف CSR و Private Key

```powershell
& $openssl req -new -newkey rsa:2048 -nodes -keyout YourDomainNameHere.test.key -out YourDomainNameHere.test.csr
```

**مثال للقيم المطلوبة:**

```
Country Name: YE
State: Sanaa
Locality: Sanaa
Organization: Yacoub
Organizational Unit: IT
Common Name: YourDomainNameHere.com
Email: yacoub@gmail.com
Challenge password: [اتركه فارغًا]
Optional company name: [اتركه فارغًا]
```

**الملفات الناتجة:**

| الملف | الاستخدام |
|-------|-----------|
| `YourDomainNameHere.test.csr` | يُرفع إلى Apple Developer |
| `YourDomainNameHere.test.key` | Private Key — **سري للغاية** |

---

## 9. رفع CSR وتنزيل شهادة Apple

1. ارجع إلى Apple Developer → Pass Type ID Certificate
2. ارفع: `C:\apple-certs\YourDomainNameHere.test.csr`
3. **Continue** → **Download**
4. احفظ الملف (غالبًا `pass.cer`) في `C:\apple-certs`

---

## 10. تحويل pass.cer إلى pass.pem

```powershell
& $openssl x509 -in pass.cer -inform DER -out pass.pem -outform PEM
ls
```

---

## 11. إنشاء ملف P12

```powershell
& $openssl pkcs12 -export -out pass.p12 -inkey YourDomainNameHere.test.key -in pass.pem
```

- **Enter Export Password:** كلمة مرور قوية
- **Verifying:** أعد كتابة نفس كلمة المرور

> **لا تنس كلمة مرور P12** — ستحتاجها في `.env` تحت `APPLE_WALLET_CERTIFICATE_PASS`.

---

## 12. تنزيل Apple WWDR Certificate

افتح: [https://www.apple.com/certificateauthority/](https://www.apple.com/certificateauthority/)

ابحث عن:

**Worldwide Developer Relations - G6** (Expiring 03/19/2036)

احفظ الملف في `C:\apple-certs` (اسمه غالبًا `AppleWWDRCAG6.cer`).

---

## 13. تحويل AppleWWDRCAG6.cer إلى PEM

```powershell
& $openssl x509 -in AppleWWDRCAG6.cer -inform DER -out AppleWWDRCAG6.pem -outform PEM
ls
```

---

## 14. الملفات النهائية المطلوبة

| الملف | حساس؟ | الاستخدام |
|-------|-------|-----------|
| `YourDomainNameHere.test.key` | نعم | Private Key |
| `YourDomainNameHere.test.csr` | لا | طُلب من Apple |
| `pass.cer` | متوسط | الشهادة الأصلية |
| `pass.pem` | متوسط | نسخة PEM |
| `pass.p12` | **نعم** | ملف الربط + كلمة مرور |
| `AppleWWDRCAG6.cer` | لا | WWDR الأصلية |
| `AppleWWDRCAG6.pem` | لا | WWDR للمكتبة |

---

## 15. ربط الشهادات بالمكتبة

### 15.1 نسخ الملفات إلى المشروع

```text
storage/app/apple-wallet/
├── pass.p12
└── AppleWWDRCAG6.pem
```

### 15.2 إعداد `.env`

```env
APPLE_WALLET_PASS_TYPE_IDENTIFIER=pass.com.example.yacoubalhaidari
APPLE_WALLET_TEAM_IDENTIFIER=XXXXXXXXXX
APPLE_WALLET_ORGANIZATION_NAME="اسم مشروعك"

APPLE_WALLET_CERTIFICATE_PATH=storage/app/apple-wallet/pass.p12
APPLE_WALLET_CERTIFICATE_PASS=your-p12-password
APPLE_WALLET_WWDR_CERTIFICATE=storage/app/apple-wallet/AppleWWDRCAG6.pem
```

### 15.3 Team Identifier

تجده في [Apple Developer → Membership](https://developer.apple.com/account) — **Team ID** (10 أحرف).

### 15.4 التحقق

```php
use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleWalletService;

$report = app(AppleWalletService::class)->configurationReport();
// كل عنصر يجب أن يكون ok => true
```

---

## 16. ملخص الأوامر كاملة

```powershell
winget install ShiningLight.OpenSSL.Light
openssl version

mkdir C:\apple-certs -Force
cd C:\apple-certs

$openssl = (Get-Command openssl).Source
& $openssl version

& $openssl req -new -newkey rsa:2048 -nodes -keyout YourDomainNameHere.test.key -out YourDomainNameHere.test.csr

& $openssl x509 -in pass.cer -inform DER -out pass.pem -outform PEM

& $openssl pkcs12 -export -out pass.p12 -inkey YourDomainNameHere.test.key -in pass.pem

& $openssl x509 -in AppleWWDRCAG6.cer -inform DER -out AppleWWDRCAG6.pem -outform PEM
```

---

## 17. مشاكل شائعة وحلول سريعة

| المشكلة | السبب | الحل |
|---------|-------|------|
| خطأ `& $openssl` | `$openssl` غير معرف | `$openssl = (Get-Command openssl).Source` |
| `openssl` غير معروف | غير مضاف إلى PATH | استخدم المسار الكامل أو أعد فتح PowerShell |
| لا يظهر Register App ID | تصنيف خاطئ | App IDs → Pass Type IDs |
| Apple يطلب CSR | طبيعي | ارفع `.csr` فقط |
| نسيت كلمة مرور P12 | لا تُستخرج بسهولة | أعد `pkcs12 -export` |
| `.pkpass` لا يُفتح | شهادة أو WWDR خاطئة | راجع `configurationReport()` |
| Pass Type ID mismatch | Identifier مختلف | طابق `.env` مع Apple Developer |

---

## 18. Checklist قبل استخدام الشهادة في المشروع

- [ ] تم إنشاء Pass Type ID
- [ ] تم إصدار Pass Type ID Certificate وتنزيل `pass.cer`
- [ ] تم تحويل `pass.cer` → `pass.pem`
- [ ] تم تصدير `pass.p12` مع كلمة مرور محفوظة
- [ ] تم تنزيل WWDR G6 وتحويلها إلى `AppleWWDRCAG6.pem`
- [ ] تم نسخ `pass.p12` و `AppleWWDRCAG6.pem` إلى `storage/app/apple-wallet/`
- [ ] تم ضبط `.env` (Pass Type ID, Team ID, Certificate, WWDR)
- [ ] لم تُرفع الملفات الحساسة إلى Git

---

# الجزء الثاني — إعداد المشروع

## 19. متغيرات `.env` الكاملة

```env
APPLE_WALLET_PASS_TYPE_IDENTIFIER=pass.com.example.yacoubalhaidari
APPLE_WALLET_TEAM_IDENTIFIER=XXXXXXXXXX
APPLE_WALLET_ORGANIZATION_NAME="اسم مشروعك"

APPLE_WALLET_CERTIFICATE_PATH=storage/app/apple-wallet/pass.p12
APPLE_WALLET_CERTIFICATE_PASS=your-p12-password
APPLE_WALLET_WWDR_CERTIFICATE=storage/app/apple-wallet/AppleWWDRCAG6.pem

APPLE_WALLET_ICON_PATH=public/images/logo.png
APPLE_WALLET_LOGO_PATH=public/images/logo.png

APPLE_WALLET_FOREGROUND_COLOR=rgb(255, 255, 255)
APPLE_WALLET_BACKGROUND_COLOR=rgb(139, 94, 60)
APPLE_WALLET_LABEL_COLOR=rgb(255, 255, 255)

APPLE_WALLET_STAMP_COLUMNS=5
APPLE_WALLET_STAMP_COMPLETED_COLOR=#E07B2D
APPLE_WALLET_STRIP_BG_OVERLAY=0.55
```

## 20. ملف الإعداد

بعد `php artisan vendor:publish --tag=apple-google-wallet-config` راجع:

`config/apple-wallet.php`

## 21. ترتيب الحقول (اختياري)

```php
// config/apple-wallet.php
'fields' => [
    'visible' => ['rewards', 'remaining', 'member', 'status'],
    'secondary' => ['rewards', 'remaining'],
    'auxiliary' => ['member', 'status'],
],
```

---

# الجزء الثالث — الاستخدام

## 22. توليد `.pkpass`

```php
use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleWalletService;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;

public function download(AppleWalletService $apple, LoyaltyProgramData $program, MemberCardData $member)
{
    if (! $apple->isConfigured()) {
        abort(503, 'Apple Wallet not configured');
    }

    $pkpass = $apple->createPass($program, $member);

    if (! $pkpass) {
        abort(500, $apple->getLastError());
    }

    return response($pkpass, 200, [
        'Content-Type' => 'application/vnd.apple.pkpass',
        'Content-Disposition' => 'attachment; filename="loyalty.pkpass"',
    ]);
}
```

## 23. Facade

```php
use Yacoubalhaidari\AppleGoogleWallet\Facades\AppleWallet;

AppleWallet::createPass($program, $member);
```

---

**الخطوة التالية:** [google-wallet.md](google-wallet.md) — أو [README](../README.md) للاستخدام المشترك.
