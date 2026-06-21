<?php

namespace Yacoubalhaidari\AppleGoogleWallet;

use Illuminate\Support\ServiceProvider;
use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleStampStripGenerator;
use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleWalletService;
use Yacoubalhaidari\AppleGoogleWallet\Apple\DefaultApplePassDefinitionBuilder;
use Yacoubalhaidari\AppleGoogleWallet\Contracts\BuildsApplePassDefinition;
use Yacoubalhaidari\AppleGoogleWallet\Contracts\BuildsGoogleLoyaltyPayload;
use Yacoubalhaidari\AppleGoogleWallet\Google\DefaultGoogleLoyaltyPayloadBuilder;
use Yacoubalhaidari\AppleGoogleWallet\Google\GoogleWalletService;
use Yacoubalhaidari\AppleGoogleWallet\Google\StampCardImageGenerator;
use Yacoubalhaidari\AppleGoogleWallet\Google\WalletImageUrlResolver;

class AppleGoogleWalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/apple-wallet.php', 'apple-wallet');
        $this->mergeConfigFrom(__DIR__ . '/../config/google-wallet.php', 'google-wallet');

        $this->app->singleton(BuildsApplePassDefinition::class, function ($app) {
            $builder = config('apple-wallet.pass_definition_builder', DefaultApplePassDefinitionBuilder::class);

            return $app->make($builder);
        });

        $this->app->singleton(BuildsGoogleLoyaltyPayload::class, function ($app) {
            $builder = config('google-wallet.payload_builder', DefaultGoogleLoyaltyPayloadBuilder::class);

            return $app->make($builder);
        });

        $this->app->singleton(AppleWalletService::class);
        $this->app->singleton(GoogleWalletService::class);
        $this->app->singleton(AppleStampStripGenerator::class);
        $this->app->singleton(StampCardImageGenerator::class);
        $this->app->singleton(WalletImageUrlResolver::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'apple-google-wallet');

        $this->publishes([
            __DIR__ . '/../config/apple-wallet.php' => config_path('apple-wallet.php'),
            __DIR__ . '/../config/google-wallet.php' => config_path('google-wallet.php'),
        ], 'apple-google-wallet-config');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/apple-google-wallet'),
        ], 'apple-google-wallet-lang');

        $this->registerAppleWalletStorageDisk();
    }

    protected function registerAppleWalletStorageDisk(): void
    {
        $diskName = (string) config('apple-wallet.storage_disk', 'apple-wallet');

        config([
            "filesystems.disks.{$diskName}" => [
                'driver' => 'local',
                'root' => storage_path('app/apple-wallet'),
                'throw' => false,
            ],
        ]);
    }
}
