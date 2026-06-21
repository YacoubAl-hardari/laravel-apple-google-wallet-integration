<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Studio;

use Yacoubalhaidari\AppleGoogleWallet\Support\AppleColorNormalizer;

class WalletStudioConfigApplier
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function apply(array $input): void
    {
        $locale = (string) ($input['preview_locale'] ?? 'ar');
        app()->setLocale($locale);

        config([
            'wallet-studio.lang' => $input['lang_' . $locale] ?? [],
        ]);

        $platform = (string) ($input['platform'] ?? 'both');

        if (in_array($platform, ['apple', 'both'], true)) {
            config([
                'apple-wallet.foreground_color' => AppleColorNormalizer::normalize($input['apple_foreground'] ?? '#FFFFFF'),
                'apple-wallet.background_color' => AppleColorNormalizer::normalize($input['apple_background'] ?? '#8B5E3C'),
                'apple-wallet.label_color' => AppleColorNormalizer::normalize($input['apple_label'] ?? '#FFFFFF'),
                'apple-wallet.stamp_columns' => (int) ($input['stamp_columns'] ?? 5),
                'apple-wallet.stamp_completed_color' => $input['apple_stamp_completed'] ?? '#E07B2D',
                'apple-wallet.stamp_empty_fill' => $input['apple_stamp_empty_fill'] ?? '#FFFFFF',
                'apple-wallet.stamp_empty_border' => $input['apple_stamp_empty_border'] ?? '#FFFFFF',
                'apple-wallet.strip_background_overlay' => (float) ($input['apple_strip_overlay'] ?? 0.55),
                'apple-wallet.fields' => [
                    'visible' => array_values($input['apple_visible_fields'] ?? ['rewards', 'remaining', 'member', 'status']),
                    'secondary' => array_values($input['apple_secondary_order'] ?? ['rewards', 'remaining']),
                    'auxiliary' => array_values($input['apple_auxiliary_order'] ?? ['member', 'status']),
                ],
            ]);

            if (! empty($input['logo_path'])) {
                $local = storage_path('app/public/' . ltrim((string) $input['logo_path'], '/'));
                config([
                    'apple-wallet.icon_path' => $local,
                    'apple-wallet.logo_path' => $local,
                ]);
            }

            if (! empty($input['logo_url'])) {
                config(['google-wallet.default_logo' => $input['logo_url']]);
            }

            if (! empty($input['strip_bg_path'])) {
                $storagePath = '/storage/' . ltrim((string) $input['strip_bg_path'], '/');
                config(['apple-wallet.strip_background_image' => $storagePath]);
                config(['google-wallet.stamp_strip_background_image' => $storagePath]);
            }
        }

        if (in_array($platform, ['google', 'both'], true)) {
            config([
                'google-wallet.hex_background_color' => $input['google_background'] ?? '#8B5E3C',
                'google-wallet.stamp_columns' => (int) ($input['stamp_columns'] ?? 5),
                'google-wallet.stamp_strip_background' => $input['google_strip_bg'] ?? '#000000',
                'google-wallet.stamp_strip_background_overlay' => (float) ($input['google_strip_overlay'] ?? 0.35),
                'google-wallet.stamp_filled_color' => $input['google_stamp_filled'] ?? '#FFFFFF',
                'google-wallet.stamp_empty_color' => $input['google_stamp_empty'] ?? '#1A1A1A',
                'google-wallet.stamp_border_color' => $input['google_stamp_border'] ?? '#FFFFFF',
                'google-wallet.stamp_text_color' => $input['google_stamp_text'] ?? '#FFFFFF',
                'google-wallet.fields' => [
                    'visible' => array_values($input['google_visible_fields'] ?? ['rewards', 'remaining', 'status']),
                    'modules' => array_values($input['google_modules_order'] ?? ['remaining', 'status']),
                ],
            ]);

            if (! empty($input['logo_url'])) {
                config(['google-wallet.default_logo' => $input['logo_url']]);
            }
        }
    }
}
