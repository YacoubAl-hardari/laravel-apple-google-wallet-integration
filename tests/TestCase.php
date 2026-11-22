<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Yacoubalhaidari\AppleGoogleWallet\AppleGoogleWalletServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [AppleGoogleWalletServiceProvider::class];
    }
}
