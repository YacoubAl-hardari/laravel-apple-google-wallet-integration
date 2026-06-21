<?php

if (! function_exists('wallet_trans')) {
    /**
     * @param  array<string, string|int|float>  $replace
     */
    function wallet_trans(string $key, array $replace = []): string
    {
        return trans(
            "apple-google-wallet::wallet.{$key}",
            $replace,
            env('WALLET_LOCALE') ?: app()->getLocale()
        );
    }
}
