<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Tests;

use Illuminate\Support\Facades\Storage;
use Yacoubalhaidari\AppleGoogleWallet\AppleGoogleWalletServiceProvider;

class StorageConfigurationTest extends TestCase
{
    public function test_preserves_a_configured_local_disk_and_writes_to_its_custom_root(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wallet-storage-'.bin2hex(random_bytes(12));
        $configuration = ['driver' => 'local', 'root' => $root, 'throw' => true, 'visibility' => 'private'];
        config([
            'apple-wallet.storage_disk' => 'custom-wallet-storage',
            'filesystems.disks.custom-wallet-storage' => $configuration,
        ]);

        (new AppleGoogleWalletServiceProvider($this->app))->boot();

        try {
            $this->assertSame($configuration, config('filesystems.disks.custom-wallet-storage'));
            $this->assertTrue(Storage::disk('custom-wallet-storage')->put('saved-pass.pkpass', 'signed pass fixture'));
            $this->assertFileExists($root.DIRECTORY_SEPARATOR.'saved-pass.pkpass');
            $this->assertSame('signed pass fixture', file_get_contents($root.DIRECTORY_SEPARATOR.'saved-pass.pkpass'));
        } finally {
            if (is_file($root.DIRECTORY_SEPARATOR.'saved-pass.pkpass')) {
                unlink($root.DIRECTORY_SEPARATOR.'saved-pass.pkpass');
            }

            if (is_dir($root)) {
                rmdir($root);
            }
        }
    }

    public function test_registers_a_local_fallback_when_the_selected_disk_is_not_configured(): void
    {
        config([
            'apple-wallet.storage_disk' => 'new-wallet-storage',
            'filesystems.disks.new-wallet-storage' => null,
        ]);

        (new AppleGoogleWalletServiceProvider($this->app))->boot();

        $this->assertSame([
            'driver' => 'local',
            'root' => storage_path('app/apple-wallet'),
            'throw' => false,
        ], config('filesystems.disks.new-wallet-storage'));
        $this->assertSame(storage_path('app/apple-wallet').DIRECTORY_SEPARATOR.'pass.pkpass', Storage::disk('new-wallet-storage')->path('pass.pkpass'));
    }

    public function test_does_not_silently_replace_a_configured_remote_disk_with_local_storage(): void
    {
        $configuration = ['driver' => 's3', 'bucket' => 'wallet-passes', 'root' => 'private/passes'];
        config([
            'apple-wallet.storage_disk' => 'remote-wallet-storage',
            'filesystems.disks.remote-wallet-storage' => $configuration,
        ]);

        (new AppleGoogleWalletServiceProvider($this->app))->boot();

        $this->assertSame($configuration, config('filesystems.disks.remote-wallet-storage'));
    }
}
