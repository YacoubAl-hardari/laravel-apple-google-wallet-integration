<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Apple;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class PkPassGenerator
{
    private string $certStore;

    private string $certStorePassword;

    private string $wwdrCertPath;

    private string $passJson;

    /** @var array<string, string> */
    private array $assets = [];

    private string $passFilename;

    private string $passRelativePath;

    private string $passRealPath;

    private string $signatureFilename = 'signature';

    private string $manifestFilename = 'manifest.json';

    private string $passJsonFilename = 'pass.json';

    public function __construct(
        string|false $passId = false,
        bool $replaceExistent = false,
        ?string $disk = null,
    ) {
        $disk ??= (string) config('apple-wallet.storage_disk', 'apple-wallet');

        $certPath = config('apple-wallet.certificate_store_path');

        if (is_file($certPath)) {
            $this->certStore = file_get_contents($certPath);
        } else {
            throw new InvalidArgumentException('No certificate found on ' . $certPath);
        }

        $this->certStorePassword = (string) config('apple-wallet.certificate_store_password');

        $wwdrCertPath = config('apple-wallet.wwdr_certificate_path');

        if (is_file($wwdrCertPath) && @openssl_x509_read(file_get_contents($wwdrCertPath))) {
            $this->wwdrCertPath = $wwdrCertPath;
        } else {
            throw new InvalidArgumentException(
                'No valid intermediate certificate was found on ' . $wwdrCertPath
            );
        }

        if (!$passId) {
            $passId = uniqid('pass_', true);
        }

        $this->passRelativePath = $passId;
        $this->passFilename = $passId . '.pkpass';

        if (Storage::disk($disk)->exists($this->passFilename)) {
            if ($replaceExistent) {
                Storage::disk($disk)->delete($this->passFilename);
            } else {
                throw new RuntimeException(
                    'The file ' . $this->passFilename . ' already exists, try another pass_id or download.'
                );
            }
        }

        $this->passRealPath = Storage::disk($disk)->path($this->passRelativePath);
        $this->disk = $disk;
    }

    private string $disk;

    public function __destruct()
    {
        Storage::disk($this->disk)->deleteDirectory($this->passRelativePath);
    }

    public function addAsset(string $assetPath): void
    {
        if (is_file($assetPath)) {
            $this->assets[basename($assetPath)] = $assetPath;

            return;
        }

        throw new InvalidArgumentException("The file $assetPath does NOT exist");
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function setPassDefinition(array $definition): void
    {
        $this->passJson = json_encode($definition, JSON_THROW_ON_ERROR);
    }

    public function create(): string
    {
        $this->createTempFolder();

        $manifest = $this->createJsonManifest();

        Storage::disk($this->disk)->put($this->passRelativePath . '/manifest.json', $manifest);

        $this->signManifest();
        $this->zipItAll();

        Storage::disk($this->disk)->move(
            $this->passRelativePath . '/' . $this->passFilename,
            $this->passFilename
        );

        Storage::disk($this->disk)->deleteDirectory($this->passRelativePath);

        return Storage::disk($this->disk)->get($this->passFilename);
    }

    public static function getPassMimeType(): string
    {
        return 'application/vnd.apple.pkpass';
    }

    private function createJsonManifest(): string
    {
        $hashes['pass.json'] = sha1($this->passJson);

        foreach ($this->assets as $filename => $path) {
            $hashes[$filename] = sha1(file_get_contents($path));
        }

        return json_encode((object) $hashes, JSON_THROW_ON_ERROR);
    }

    private function removeMimeBS(string $emailSignature): string
    {
        $lastHeaderLine = 'Content-Disposition: attachment; filename="smime.p7s"';
        $footerLineStart = "\n------";
        $firstSignatureLine = mb_strpos($emailSignature, "\n", mb_strpos($emailSignature, $lastHeaderLine));
        $cleanSignature = mb_strcut($emailSignature, $firstSignatureLine + 1);
        $endOfSignature = mb_strpos($cleanSignature, $footerLineStart);
        $cleanSignature = mb_strcut($cleanSignature, 0, $endOfSignature);

        return base64_decode(trim($cleanSignature));
    }

    private function signManifest(): void
    {
        $manifestPath = $this->passRealPath . '/' . $this->manifestFilename;
        $signaturePath = $this->passRealPath . '/' . $this->signatureFilename;
        $certs = [];

        if (!openssl_pkcs12_read($this->certStore, $certs, $this->certStorePassword)) {
            throw new RuntimeException('The certificate could not be read.');
        }

        $certResource = openssl_x509_read($certs['cert']);
        $privateKey = openssl_pkey_get_private($certs['pkey'], $this->certStorePassword);

        openssl_pkcs7_sign(
            $manifestPath,
            $signaturePath,
            $certResource,
            $privateKey,
            [],
            PKCS7_BINARY | PKCS7_DETACHED,
            $this->wwdrCertPath
        );

        $signature = Storage::disk($this->disk)->get($this->passRelativePath . '/' . $this->signatureFilename);
        $signature = $this->removeMimeBS($signature);

        Storage::disk($this->disk)->put($this->passRelativePath . '/' . $this->signatureFilename, $signature);
    }

    private function zipItAll(): void
    {
        $zipPath = $this->passRealPath . '/' . $this->passFilename;
        $manifestPath = $this->passRealPath . '/' . $this->manifestFilename;
        $signaturePath = $this->passRealPath . '/' . $this->signatureFilename;

        $zip = new ZipArchive();

        if (!$zip->open($zipPath, ZipArchive::CREATE)) {
            throw new RuntimeException('There was a problem while creating the zip file');
        }

        $zip->addFile($manifestPath, $this->manifestFilename);
        $zip->addFile($signaturePath, $this->signatureFilename);
        $zip->addFromString($this->passJsonFilename, $this->passJson);

        foreach ($this->assets as $name => $path) {
            $zip->addFile($path, $name);
        }

        $zip->close();
    }

    private function createTempFolder(): void
    {
        if (!is_dir($this->passRealPath)) {
            Storage::disk($this->disk)->makeDirectory($this->passRelativePath);
        }
    }
}
