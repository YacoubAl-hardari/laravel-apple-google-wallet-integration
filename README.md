# Laravel Apple & Google Wallet Integration

Laravel package for generating **Apple Wallet** (`.pkpass`) and **Google Wallet** loyalty stamp cards by **Yacoub Alhaidari**.

## Features

- Apple Wallet `.pkpass` generation with stamp strip images
- Google Wallet Loyalty Class/Object with JWT save URLs
- Stamp card image generators (GD) for both platforms
- Public HTTPS image URL resolver for Google Wallet
- **DTO-based API** — no Eloquent model coupling
- **Extensible builders** via contracts + config
- **Events** for customizing payloads
- **Arabic & English** translations (`lang/ar`, `lang/en`)
- Config renamed: `apple-wallet.php` (replaces `passgenerator.php`)

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- OpenSSL, Zip, GD extensions
- Apple: Pass Type ID certificate (`.p12`) + WWDR certificate
- Google: Wallet API service account JSON + Issuer ID

## Installation

### Local path (for testing)

In your Laravel project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../your-project/packages/laravel-apple-google-wallet-integration"
        }
    ],
    "require": {
        "yacoubalhaidari/laravel-apple-google-wallet-integration": "@dev"
    }
}
```

Then:

```bash
composer update yacoubalhaidari/laravel-apple-google-wallet-integration
php artisan vendor:publish --tag=apple-google-wallet-config
```

### Publish config

```bash
php artisan vendor:publish --tag=apple-google-wallet-config
```

This publishes:

- `config/apple-wallet.php`
- `config/google-wallet.php`

## Configuration

### Apple Wallet (`.env`)

```env
APPLE_WALLET_PASS_TYPE_IDENTIFIER=pass.com.example.loyalty
APPLE_WALLET_TEAM_IDENTIFIER=XXXXXXXXXX
APPLE_WALLET_ORGANIZATION_NAME="My App"
APPLE_WALLET_CERTIFICATE_PATH=storage/app/apple-wallet/pass.p12
APPLE_WALLET_CERTIFICATE_PASS=your-password
APPLE_WALLET_WWDR_CERTIFICATE=storage/app/apple-wallet/AppleWWDRCAG6.pem
APPLE_WALLET_ICON_PATH=public/images/logo.png
APPLE_WALLET_LOGO_PATH=public/images/logo.png
```

### Google Wallet (`.env`)

```env
GOOGLE_WALLET_ISSUER_ID=3388000000000000000
GOOGLE_WALLET_SERVICE_ACCOUNT_JSON=storage/app/google-wallet/service-account.json
GOOGLE_WALLET_ISSUER_NAME="My App"
GOOGLE_WALLET_FALLBACK_LOGO=https://cdn.example.com/logo.png
GOOGLE_WALLET_PUBLIC_ASSET_BASE_URL=https://cdn.example.com
```

### Locale (Arabic / English)

```env
# ar or en — falls back to APP_LOCALE when not set
WALLET_LOCALE=ar
```

Publish translations to customize labels:

```bash
php artisan vendor:publish --tag=apple-google-wallet-lang
```

Files are published to `lang/vendor/apple-google-wallet/{ar,en}/wallet.php`.

## Usage

### Prepare DTOs

```php
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;

$program = new LoyaltyProgramData(
    id: '1',
    name: 'بطاقة الولاء',
    requiredStamps: 10,
    rewardCount: 1,
    imageUrl: 'https://cdn.example.com/card.png',
);

$member = new MemberCardData(
    id: 42,
    qrCode: 'QR-ABC-123',
    stampsProgress: 3,
    rewardsEarned: 0,
    isCompleted: false,
    memberName: 'أحمد محمد',
);

// Or from arrays (supports legacy field names):
$program = LoyaltyProgramData::fromArray($loyaltyCard->toArray());
$member = MemberCardData::fromArray($userCard->toArray());
```

### Apple Wallet

```php
use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleWalletService;

public function download(AppleWalletService $appleWallet)
{
    if (!$appleWallet->isConfigured()) {
        abort(503, 'Apple Wallet not configured');
    }

    $pkpass = $appleWallet->createPass($program, $member);

    if (!$pkpass) {
        abort(500, $appleWallet->getLastError());
    }

    return response($pkpass, 200, [
        'Content-Type' => 'application/vnd.apple.pkpass',
        'Content-Disposition' => 'attachment; filename="loyalty.pkpass"',
    ]);
}
```

### Google Wallet

```php
use Yacoubalhaidari\AppleGoogleWallet\Google\GoogleWalletService;

public function save(GoogleWalletService $googleWallet)
{
    $url = $googleWallet->saveUrl($program, $member);

    return redirect()->away($url);
}

// Update stamps after a visit:
$googleWallet->updateLoyaltyCard($program, $member);
```

### Facades

```php
use Yacoubalhaidari\AppleGoogleWallet\Facades\AppleWallet;
use Yacoubalhaidari\AppleGoogleWallet\Facades\GoogleWallet;

AppleWallet::createPass($program, $member);
GoogleWallet::saveUrl($program, $member);
```

## Extensibility

### Custom Apple pass definition

Implement `BuildsApplePassDefinition` and register in config:

```php
// config/apple-wallet.php
'pass_definition_builder' => App\Wallet\CustomApplePassBuilder::class,
```

Or bind in `AppServiceProvider`:

```php
$this->app->singleton(
    \Yacoubalhaidari\AppleGoogleWallet\Contracts\BuildsApplePassDefinition::class,
    \App\Wallet\CustomApplePassBuilder::class
);
```

### Custom Google payload

Implement `BuildsGoogleLoyaltyPayload`:

```php
// config/google-wallet.php
'payload_builder' => App\Wallet\CustomGooglePayloadBuilder::class,
```

### Events

Listen to customize before the pass/object is finalized:

```php
use Yacoubalhaidari\AppleGoogleWallet\Events\ApplePassDefinitionBuilding;
use Yacoubalhaidari\AppleGoogleWallet\Events\GoogleLoyaltyObjectBuilding;

Event::listen(ApplePassDefinitionBuilding::class, function ($event) {
    // Inspect $event->definition, $event->program, $event->member
});

Event::listen(GoogleLoyaltyObjectBuilding::class, function ($event) {
    // Modify $event->object via setters
});
```

### Bridge from Eloquent models

Create a thin adapter in your app:

```php
class WalletDataFactory
{
    public static function program(LoyaltyCard $card): LoyaltyProgramData
    {
        return LoyaltyProgramData::fromArray([
            'id' => $card->id,
            'name' => $card->name,
            'required_washes' => $card->required_washes,
            'reward_free_washes' => $card->reward_free_washes,
            'image' => $card->image,
        ]);
    }

    public static function member(User $user, UserLoyaltyCard $userCard): MemberCardData
    {
        return MemberCardData::fromArray([
            'id' => $userCard->id,
            'qr_code' => $userCard->qr_code,
            'washes_progress' => $userCard->washes_progress,
            'rewards_earned' => $userCard->rewards_earned,
            'is_completed' => $userCard->is_completed,
            'member_name' => trim($user->first_name . ' ' . $user->last_name),
        ]);
    }
}
```

## Storage

The package registers an `apple-wallet` filesystem disk at `storage/app/apple-wallet` for temporary `.pkpass` build files. Stamp images are saved to your configured public disk (default: `public`).

Ensure the public storage link exists:

```bash
php artisan storage:link
```

## Migration from in-app services

| Old | New |
|-----|-----|
| `config/passgenerator.php` | `config/apple-wallet.php` |
| `config('passgenerator.*')` | `config('apple-wallet.*')` |
| `App\Services\AppleLoyaltyCard\*` | `Yacoubalhaidari\AppleGoogleWallet\Apple\*` |
| `App\Services\GoogleLoyaltyCard\*` | `Yacoubalhaidari\AppleGoogleWallet\Google\*` |
| Eloquent models in service methods | `LoyaltyProgramData` + `MemberCardData` DTOs |

## License

MIT
