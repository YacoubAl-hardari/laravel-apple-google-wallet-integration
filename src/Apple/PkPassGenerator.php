<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Apple;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use ZipArchive;

class PkPassGenerator
{
    private string $certStore;

    private string $certStorePassword;

    private string $wwdrCertPath;

    private ?string $passJson = null;

    /** @var array<string, string> */
    private array $assets = [];

    private string $passFilename;

    private string $disk;

    private ?string $workingDirectory = null;

    public function __construct(
        string|false $passId = false,
        private bool $replaceExistent = false,
        ?string $disk = null,
    ) {
        $passId = $passId === false ? 'pass_'.bin2hex(random_bytes(16)) : $passId;

        if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{0,159}\z/', $passId)
            || preg_match('/\A(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|\z)/i', $passId)) {
            throw new InvalidArgumentException('The pass ID must be a safe filename of at most 160 characters.');
        }

        $this->disk = $disk ?? (string) config('apple-wallet.storage_disk', 'apple-wallet');
        $this->passFilename = $passId.'.pkpass';

        if (config('filesystems.disks.'.$this->disk.'.driver') !== 'local') {
            throw new InvalidArgumentException('Apple Wallet pass generation requires a local filesystem disk. Configure disk "'.$this->disk.'" with driver "local".');
        }

        if (! $this->replaceExistent && Storage::disk($this->disk)->exists($this->passFilename)) {
            throw new RuntimeException('The pass already exists; use a different pass ID or allow replacement.');
        }

        $certPath = (string) config('apple-wallet.certificate_store_path');
        $certStore = is_file($certPath) && is_readable($certPath) ? file_get_contents($certPath) : false;

        if ($certStore === false) {
            throw new InvalidArgumentException('The signing certificate store is not readable.');
        }

        $this->certStore = $certStore;
        $this->certStorePassword = (string) config('apple-wallet.certificate_store_password');
        $this->wwdrCertPath = (string) config('apple-wallet.wwdr_certificate_path');
        $wwdrCertificate = is_file($this->wwdrCertPath) && is_readable($this->wwdrCertPath)
            ? file_get_contents($this->wwdrCertPath)
            : false;

        if ($wwdrCertificate === false || @openssl_x509_read($wwdrCertificate) === false) {
            throw new InvalidArgumentException('A valid intermediate certificate is required.');
        }
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    public function addAsset(string $assetPath, ?string $archiveName = null): void
    {
        $archiveName ??= basename($assetPath);

        if (! preg_match('/\A(?:[A-Za-z0-9_-]+\.lproj\/)?[A-Za-z0-9][A-Za-z0-9@._-]*\z/', $archiveName)
            || in_array($archiveName, ['pass.json', 'manifest.json', 'signature'], true)) {
            throw new InvalidArgumentException('The asset archive name is not permitted.');
        }

        $contents = is_file($assetPath) && is_readable($assetPath) ? file_get_contents($assetPath) : false;

        if ($contents === false) {
            throw new InvalidArgumentException('The pass asset is not readable.');
        }

        $this->assets[$archiveName] = $contents;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function setPassDefinition(array $definition): void
    {
        $this->passJson = json_encode($definition, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public function create(): string
    {
        if ($this->passJson === null) {
            throw new RuntimeException('Set the pass definition before creating a pass.');
        }

        try {
            $this->createTempFolder();
            $manifest = $this->createJsonManifest();
            $manifestPath = $this->workingPath('manifest.json');

            if (file_put_contents($manifestPath, $manifest) !== strlen($manifest)) {
                throw new RuntimeException('The pass manifest could not be written.');
            }

            $this->signManifest();
            $archivePath = $this->zipItAll($manifest);
            $contents = file_get_contents($archivePath);

            if ($contents === false) {
                throw new RuntimeException('The signed pass archive could not be read.');
            }

            $this->publish($archivePath);

            return $contents;
        } finally {
            $this->cleanup();
        }
    }

    public static function getPassMimeType(): string
    {
        return 'application/vnd.apple.pkpass';
    }

    private function createJsonManifest(): string
    {
        $hashes = ['pass.json' => sha1($this->passJson)];

        foreach ($this->assets as $filename => $contents) {
            $hashes[$filename] = sha1($contents);
        }

        return json_encode($hashes, JSON_THROW_ON_ERROR);
    }

    private function signManifest(): void
    {
        $certificates = [];

        if (! @openssl_pkcs12_read($this->certStore, $certificates, $this->certStorePassword)) {
            throw new RuntimeException('The signing certificate store could not be opened.');
        }

        $certificate = openssl_x509_read($certificates['cert']);
        $privateKey = openssl_pkey_get_private($certificates['pkey']);

        if ($certificate === false || $privateKey === false
            || ! openssl_x509_check_private_key($certificate, $privateKey)) {
            throw new RuntimeException('The signing certificate and private key are invalid.');
        }

        $signed = @openssl_cms_sign(
            $this->workingPath('manifest.json'),
            $this->workingPath('signature'),
            $certificate,
            $privateKey,
            [],
            OPENSSL_CMS_BINARY | OPENSSL_CMS_DETACHED,
            OPENSSL_ENCODING_DER,
            $this->wwdrCertPath,
        );

        if (! $signed || ! is_file($this->workingPath('signature'))
            || filesize($this->workingPath('signature')) === 0) {
            throw new RuntimeException('The pass manifest could not be signed.');
        }
    }

    private function zipItAll(string $manifest): string
    {
        $archivePath = $this->workingPath('pass.pkpass');
        $zip = new ZipArchive;

        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('The pass archive could not be created.');
        }

        try {
            if (! $zip->addFromString('manifest.json', $manifest)
                || ! $zip->addFile($this->workingPath('signature'), 'signature')
                || ! $zip->addFromString('pass.json', $this->passJson)) {
                throw new RuntimeException('The signed pass metadata could not be added to the archive.');
            }

            foreach ($this->assets as $name => $contents) {
                if (! $zip->addFromString($name, $contents)) {
                    throw new RuntimeException('A pass asset could not be added to the archive.');
                }
            }
        } catch (Throwable $exception) {
            $zip->close();

            throw $exception;
        }

        if (! $zip->close()) {
            throw new RuntimeException('The pass archive could not be finalized.');
        }

        return $archivePath;
    }

    private function publish(string $archivePath): void
    {
        $targetPath = Storage::disk($this->disk)->path($this->passFilename);
        $lockPath = Storage::disk($this->disk)->path('.locks/'.hash('sha256', $this->passFilename).'.lock');

        if (! Storage::disk($this->disk)->makeDirectory('.locks')) {
            throw new RuntimeException('The pass publication lock directory could not be created.');
        }

        $lock = fopen($lockPath, 'c+b');

        if ($lock === false) {
            throw new RuntimeException('The pass publication lock could not be opened.');
        }

        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('The pass publication lock could not be acquired.');
            }

            if (! $this->replaceExistent && is_file($targetPath)) {
                throw new RuntimeException('The pass already exists; use a different pass ID or allow replacement.');
            }

            if (! @rename($archivePath, $targetPath)) {
                throw new RuntimeException('The signed pass could not be published.');
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function createTempFolder(): void
    {
        if (! Storage::disk($this->disk)->makeDirectory('.tmp')) {
            throw new RuntimeException('The pass working directory could not be created.');
        }

        $directory = '.tmp/build_'.bin2hex(random_bytes(16));

        if (! mkdir(Storage::disk($this->disk)->path($directory), 0700)) {
            throw new RuntimeException('The pass working directory could not be created.');
        }

        $this->workingDirectory = $directory;
    }

    private function workingPath(string $filename): string
    {
        return Storage::disk($this->disk)->path($this->workingDirectory.'/'.$filename);
    }

    private function cleanup(): void
    {
        if ($this->workingDirectory === null) {
            return;
        }

        try {
            Storage::disk($this->disk)->deleteDirectory($this->workingDirectory);
        } catch (Throwable) {
            return;
        } finally {
            $this->workingDirectory = null;
        }
    }
}
