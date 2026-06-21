<?php

if (! function_exists('wallet_trans')) {
    /**
     * @param  array<string, string|int|float>  $replace
     */
    function wallet_trans(string $key, array $replace = []): string
    {
        $locale = env('WALLET_LOCALE') ?: app()->getLocale();
        $overrides = config('wallet-studio.lang');

        if (is_array($overrides) && array_key_exists($key, $overrides)) {
            $line = (string) $overrides[$key];
            foreach ($replace as $placeholder => $value) {
                $line = str_replace(':' . $placeholder, (string) $value, $line);
            }

            return $line;
        }

        return trans("apple-google-wallet::wallet.{$key}", $replace, $locale);
    }
}
