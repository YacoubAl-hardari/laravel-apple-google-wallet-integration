<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Tests\Apple;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Yacoubalhaidari\AppleGoogleWallet\Apple\PkPassGenerator;
use Yacoubalhaidari\AppleGoogleWallet\Tests\TestCase;

class PkPassGeneratorTest extends TestCase
{
    use CreatesSigningCertificates;

    public function test_creates_a_signed_archive_whose_manifest_matches_every_packaged_file(): void
    {
        $this->configureAppleSigning();
        $pass = new PkPassGenerator('test-pass');
        $pass->setPassDefinition(['description' => 'بطاقة تجريبية', 'formatVersion' => 1]);
        $pass->addAsset(config('apple-wallet.icon_path'), 'icon.png');

        $contents = $pass->create();

        $files = $this->readPass($contents);
        $manifest = json_decode($files['manifest.json'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['pass.json', 'icon.png'], array_keys($manifest));

        foreach ($manifest as $name => $hash) {
            $this->assertSame($hash, sha1($files[$name]));
        }

        $fixtures = Storage::disk('wallet-fixtures');
        $fixtures->put('manifest.json', $files['manifest.json']);
        $fixtures->put('signature', $files['signature']);
        while (openssl_error_string() !== false) {
        }
        $verified = openssl_cms_verify(
            $fixtures->path('manifest.json'),
            OPENSSL_CMS_BINARY | OPENSSL_CMS_DETACHED | OPENSSL_CMS_NOVERIFY,
            null,
            [],
            null,
            null,
            null,
            $fixtures->path('signature'),
            OPENSSL_ENCODING_DER,
        );
        $errors = [];
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }
        $this->assertTrue($verified, implode('; ', $errors));
        $this->assertSame($contents, Storage::disk('apple-wallet')->get('test-pass.pkpass'));
        $this->assertSame([], Storage::disk('apple-wallet')->allDirectories('.tmp'));
    }

    public function test_snapshots_assets_so_interleaved_builds_do_not_mix_artwork(): void
    {
        $this->configureAppleSigning();
        $fixtures = Storage::disk('wallet-fixtures');
        $fixtures->put('merchant.png', 'merchant A artwork');
        $first = new PkPassGenerator('same-card', true);
        $first->setPassDefinition(['serialNumber' => 'merchant-a']);
        $first->addAsset($fixtures->path('merchant.png'), 'logo.png');
        $fixtures->put('merchant.png', 'merchant B artwork');
        $second = new PkPassGenerator('same-card', true);
        $second->setPassDefinition(['serialNumber' => 'merchant-b']);
        $second->addAsset($fixtures->path('merchant.png'), 'logo.png');

        $firstContents = $first->create();
        $secondContents = $second->create();

        $this->assertSame('merchant A artwork', $this->readPass($firstContents)['logo.png']);
        $this->assertSame('merchant B artwork', $this->readPass($secondContents)['logo.png']);
        $this->assertSame($secondContents, Storage::disk('apple-wallet')->get('same-card.pkpass'));
    }

    public function test_preserves_the_last_good_pass_when_replacement_signing_fails(): void
    {
        $this->configureAppleSigning();
        $disk = Storage::disk('apple-wallet');
        $disk->put('existing-card.pkpass', 'last good signed pass');
        config(['apple-wallet.certificate_store_password' => 'wrong-password']);
        $pass = new PkPassGenerator('existing-card', true);
        $pass->setPassDefinition(['serialNumber' => 'existing-card']);

        try {
            $pass->create();
            $this->fail('A signing failure must reject the new pass.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The signing certificate store could not be opened.', $exception->getMessage());
        }

        $this->assertSame('last good signed pass', $disk->get('existing-card.pkpass'));
        $this->assertSame([], $disk->allDirectories('.tmp'));
    }

    public function test_refuses_a_late_conflict_when_two_non_replacing_builds_share_a_pass_id(): void
    {
        $this->configureAppleSigning();
        $first = new PkPassGenerator('same-card');
        $first->setPassDefinition(['serialNumber' => 'first']);
        $second = new PkPassGenerator('same-card');
        $second->setPassDefinition(['serialNumber' => 'second']);
        $contents = $first->create();

        try {
            $second->create();
            $this->fail('The existing pass must not be replaced.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already exists', $exception->getMessage());
        }

        $this->assertSame($contents, Storage::disk('apple-wallet')->get('same-card.pkpass'));
    }

    #[DataProvider('unsafePassIds')]
    public function test_rejects_pass_ids_that_can_escape_storage_or_use_reserved_windows_names(string $passId): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PkPassGenerator($passId);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafePassIds(): array
    {
        return [
            'parent path' => ['../outside'],
            'windows parent path' => ['..\\outside'],
            'absolute path' => ['C:\\outside'],
            'empty' => [''],
            'reserved windows name' => ['CON'],
        ];
    }

    #[DataProvider('unsafeAssetNames')]
    public function test_rejects_assets_that_can_replace_signed_metadata_or_escape_the_archive(string $name): void
    {
        $this->configureAppleSigning();
        $pass = new PkPassGenerator('test-pass');
        $this->expectException(InvalidArgumentException::class);

        $pass->addAsset(config('apple-wallet.icon_path'), $name);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeAssetNames(): array
    {
        return [
            'pass definition' => ['pass.json'],
            'manifest' => ['manifest.json'],
            'signature' => ['signature'],
            'parent path' => ['../icon.png'],
            'windows path' => ['..\\icon.png'],
        ];
    }

    public function test_reports_a_missing_certificate_without_a_destructor_error(): void
    {
        Storage::fake('apple-wallet');
        config(['apple-wallet.certificate_store_path' => Storage::disk('apple-wallet')->path('missing.p12')]);
        $this->expectException(InvalidArgumentException::class);

        new PkPassGenerator('test-pass');
    }

    public function test_requires_a_definition_before_creating_a_pass(): void
    {
        $this->configureAppleSigning();
        $pass = new PkPassGenerator('test-pass');
        $this->expectException(RuntimeException::class);

        $pass->create();
    }

    public function test_rejects_non_local_storage_before_resolving_an_unsupported_adapter(): void
    {
        config(['filesystems.disks.remote-wallet' => ['driver' => 's3']]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Apple Wallet pass generation requires a local filesystem disk.');

        new PkPassGenerator('test-pass', disk: 'remote-wallet');
    }
}
