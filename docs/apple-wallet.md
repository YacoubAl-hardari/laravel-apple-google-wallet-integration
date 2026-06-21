# Apple Wallet Guide

A practical guide for setting up and using **Apple Wallet** with `yacoubalhaidari/laravel-apple-google-wallet-integration`.

| File                                 | Content                                     |
| ------------------------------------ | ------------------------------------------- |
| This file                            | Apple Wallet certificates, setup, and usage |
| [google-wallet.md](google-wallet.md) | Google Wallet guide                         |
| [README](../README.md)               | Package overview                            |

## Overview

This guide walks through the Apple Wallet certificate flow end to end:

- Create a **Pass Type ID** in Apple Developer.
- Generate a **CSR** and **private key** on Windows using OpenSSL.
- Download the Apple certificate and convert it from **CER** to **PEM**.
- Export a **P12** file with a password.
- Download the **Apple WWDR** certificate and convert it to **PEM**.

## Contents

1. Goal
2. Prerequisites
3. Create Pass Type ID
4. Create Pass Type ID Certificate
5. Install OpenSSL
6. Prepare a working folder
7. Locate OpenSSL
8. Generate CSR and private key
9. Upload CSR and download Apple certificate
10. Convert `pass.cer` to `pass.pem`
11. Create `pass.p12`
12. Download Apple WWDR
13. Convert WWDR to PEM
14. Required final files
15. Wire the certificates into the package
16. Command summary
17. Common issues
18. Checklist
19. Full `.env` variables
20. Config file reference
21. Optional field order
22. Generate `.pkpass`
23. Facade usage

## 1. Goal

This guide explains how to prepare Apple Wallet passes step by step so you can use them in a Laravel project with this package.

## 2. Prerequisites

- An active [Apple Developer](https://developer.apple.com) account
- Access to **Certificates, Identifiers & Profiles**
- Windows with **PowerShell**
- **OpenSSL** installed, or available through `winget`
- A clear domain name to use in the identifier

Keep your **private key**, **P12**, and P12 password private.

## 3. Create Pass Type ID

1. Sign in at [developer.apple.com](https://developer.apple.com)
2. Open [Identifiers](https://developer.apple.com/account/resources/identifiers/list)
3. Choose **App IDs** > **Pass Type IDs** > **Continue**
4. Fill in:

| Field       | Value                                 |
| ----------- | ------------------------------------- |
| Description | Any clear project name                |
| Identifier  | `pass.com.example.YourDomainNameHere` |

Example: `pass.com.example.yacoubalhaidari`

## 4. Create Pass Type ID Certificate

1. Open [Certificates](https://developer.apple.com/account/resources/certificates/list)
2. Choose **Development** under **All Types**
3. Click **+** and select **Pass Type ID Certificate**
4. Select the Pass Type ID you created
5. Upload the CSR generated later in step 8
6. Download the certificate

## 5. Install OpenSSL on Windows

```powershell
winget install ShiningLight.OpenSSL.Light
```

Restart PowerShell, then verify:

```powershell
openssl version
```

## 6. Prepare a working folder

```powershell
mkdir C:\apple-certs -Force
cd C:\apple-certs
```

## 7. Locate OpenSSL

```powershell
$openssl = (Get-Command openssl).Source
& $openssl version
```

If that fails, try a full path:

```powershell
$openssl = "C:\Program Files\OpenSSL-Win64\bin\openssl.exe"
& $openssl version
```

```powershell
$openssl = "C:\Program Files (x86)\OpenSSL-Win32\bin\openssl.exe"
& $openssl version
```

## 8. Generate CSR and private key

```powershell
& $openssl req -new -newkey rsa:2048 -nodes -keyout YourDomainNameHere.test.key -out YourDomainNameHere.test.csr
```

Example values:

```text
Country Name: YE
State: Sanaa
Locality: Sanaa
Organization: Yacoub
Organizational Unit: IT
Common Name: YourDomainNameHere.com
Email: yacoub@gmail.com
Challenge password: [leave blank]
Optional company name: [leave blank]
```

Generated files:

| File                          | Use                       |
| ----------------------------- | ------------------------- |
| `YourDomainNameHere.test.csr` | Upload to Apple Developer |
| `YourDomainNameHere.test.key` | Private key, keep secret  |

## 9. Upload CSR and download Apple certificate

1. Return to Apple Developer → Pass Type ID Certificate
2. Upload `C:\apple-certs\YourDomainNameHere.test.csr`
3. Download the certificate, usually `pass.cer`

## 10. Convert `pass.cer` to `pass.pem`

```powershell
& $openssl x509 -in pass.cer -inform DER -out pass.pem -outform PEM
```

## 11. Create `pass.p12`

```powershell
& $openssl pkcs12 -export -out pass.p12 -inkey YourDomainNameHere.test.key -in pass.pem
```

Enter a strong export password and store it safely.

## 12. Download Apple WWDR

Open [Apple Certificate Authority](https://www.apple.com/certificateauthority/) and download **Worldwide Developer Relations - G6**.

Save it as `AppleWWDRCAG6.cer` in the same working folder.

## 13. Convert WWDR to PEM

```powershell
& $openssl x509 -in AppleWWDRCAG6.cer -inform DER -out AppleWWDRCAG6.pem -outform PEM
```

## 14. Required final files

| File                          | Sensitive? | Use                        |
| ----------------------------- | ---------- | -------------------------- |
| `YourDomainNameHere.test.key` | Yes        | Private key                |
| `YourDomainNameHere.test.csr` | No         | Sent to Apple              |
| `pass.cer`                    | Medium     | Original certificate       |
| `pass.pem`                    | Medium     | PEM copy                   |
| `pass.p12`                    | Yes        | Package certificate bundle |
| `AppleWWDRCAG6.cer`           | No         | WWDR original              |
| `AppleWWDRCAG6.pem`           | No         | WWDR for the package       |

## 15. Wire the certificates into the package

Copy the files into:

```text
storage/app/apple-wallet/
├── pass.p12
└── AppleWWDRCAG6.pem
```

Set `.env` values:

```env
APPLE_WALLET_PASS_TYPE_IDENTIFIER=pass.com.example.yacoubalhaidari
APPLE_WALLET_TEAM_IDENTIFIER=XXXXXXXXXX
APPLE_WALLET_ORGANIZATION_NAME="Your Brand"

APPLE_WALLET_CERTIFICATE_PATH=storage/app/apple-wallet/pass.p12
APPLE_WALLET_CERTIFICATE_PASS=your-p12-password
APPLE_WALLET_WWDR_CERTIFICATE=storage/app/apple-wallet/AppleWWDRCAG6.pem
```

Team Identifier is your 10-character Apple Developer Team ID.

Verify the configuration:

```php
use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleWalletService;

$report = app(AppleWalletService::class)->configurationReport();
```

## 16. Command summary

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

## 17. Common issues

| Problem                 | Cause                     | Fix                                  |
| ----------------------- | ------------------------- | ------------------------------------ |
| `& $openssl` fails      | `$openssl` is not set     | Assign it with `Get-Command openssl` |
| `openssl` not found     | Not in PATH               | Use a full path or reopen PowerShell |
| Apple asks for CSR      | Expected                  | Upload the `.csr` file               |
| Forgot P12 password     | Hard to recover           | Re-export `pass.p12`                 |
| `.pkpass` does not open | Wrong certificate or WWDR | Recheck `configurationReport()`      |

## 18. Checklist

- [ ] Pass Type ID created
- [ ] Pass Type ID Certificate issued and downloaded
- [ ] `pass.cer` converted to `pass.pem`
- [ ] `pass.p12` exported and password stored
- [ ] WWDR G6 downloaded and converted to `AppleWWDRCAG6.pem`
- [ ] Files copied to `storage/app/apple-wallet/`
- [ ] `.env` values configured
- [ ] Sensitive files are not committed to Git

## 19. Full `.env` variables

```env
APPLE_WALLET_PASS_TYPE_IDENTIFIER=pass.com.example.yacoubalhaidari
APPLE_WALLET_TEAM_IDENTIFIER=XXXXXXXXXX
APPLE_WALLET_ORGANIZATION_NAME="Your Brand"

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

## 20. Config file reference

After publishing the config, check [config/apple-wallet.php](../config/apple-wallet.php).

## 21. Optional field order

```php
// config/apple-wallet.php
'fields' => [
    'visible' => ['rewards', 'remaining', 'member', 'status'],
    'secondary' => ['rewards', 'remaining'],
    'auxiliary' => ['member', 'status'],
],
```

## 22. Generate `.pkpass`

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

See [google-wallet.md](google-wallet.md) for the Google Wallet guide.
