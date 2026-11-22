<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Tests\Apple;

use Illuminate\Support\Facades\Storage;
use ZipArchive;

trait CreatesSigningCertificates
{
    protected function configureAppleSigning(string $password = 'test-password'): void
    {
        Storage::fake('apple-wallet');
        Storage::fake('wallet-fixtures');
        $fixtures = Storage::disk('wallet-fixtures');
        $fixtures->put('openssl.cnf', "[req]\ndistinguished_name=req_dn\n[req_dn]\n[v3_ca]\nbasicConstraints=critical,CA:TRUE\n[v3_leaf]\nbasicConstraints=critical,CA:FALSE\n");
        $options = ['config' => $fixtures->path('openssl.cnf'), 'digest_alg' => 'sha256', 'private_key_bits' => 2048];
        $authorityKey = openssl_pkey_new($options);
        $authorityRequest = openssl_csr_new(['commonName' => 'Wallet test authority'], $authorityKey, $options);
        $authority = openssl_csr_sign($authorityRequest, null, $authorityKey, 1, [...$options, 'x509_extensions' => 'v3_ca'], 1);
        $signingKey = openssl_pkey_new($options);
        $signingRequest = openssl_csr_new(['commonName' => 'Wallet test signing certificate'], $signingKey, $options);
        $certificate = openssl_csr_sign($signingRequest, $authority, $authorityKey, 1, [...$options, 'x509_extensions' => 'v3_leaf'], 2);
        $this->assertTrue(openssl_x509_export($authority, $authorityPem));
        $this->assertTrue(openssl_pkcs12_export($certificate, $store, $signingKey, $password));
        $fixtures->put('authority.pem', $authorityPem);
        $fixtures->put('pass.p12', $store);
        $fixtures->put('icon.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jVn8AAAAASUVORK5CYII=', true));

        config([
            'apple-wallet.pass_type_identifier' => 'pass.com.example.loyalty',
            'apple-wallet.team_identifier' => 'EXAMPLETEAM',
            'apple-wallet.organization_name' => 'Example merchant',
            'apple-wallet.certificate_store_path' => $fixtures->path('pass.p12'),
            'apple-wallet.certificate_store_password' => $password,
            'apple-wallet.wwdr_certificate_path' => $fixtures->path('authority.pem'),
            'apple-wallet.icon_path' => $fixtures->path('icon.png'),
            'apple-wallet.logo_path' => $fixtures->path('icon.png'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function readPass(string $contents): array
    {
        $path = Storage::disk('wallet-fixtures')->path('inspect.pkpass');
        file_put_contents($path, $contents);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $files = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $files[$zip->getNameIndex($index)] = $zip->getFromIndex($index);
        }

        $zip->close();

        return $files;
    }
}
