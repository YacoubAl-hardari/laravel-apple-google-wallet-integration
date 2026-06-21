<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Studio;

class WalletStudioDefaults
{
    /**
     * @return array<string, mixed>
     */
    public static function form(): array
    {
        return [
            'platform' => 'both',
            'preview_locale' => env('WALLET_LOCALE', app()->getLocale()),
            'preview_stamps_filled' => 3,
            'preview_stamps_total' => 10,
            'preview_rewards' => 0,
            'preview_member' => 'أحمد محمد',
            'preview_program' => 'بطاقة الولاء',
            'logo_path' => null,
            'logo_url' => null,
            'strip_bg_path' => null,
            'strip_bg_url' => null,

            'apple_background' => '#8B5E3C',
            'apple_foreground' => '#FFFFFF',
            'apple_label' => '#FFFFFF',
            'apple_stamp_completed' => '#E07B2D',
            'apple_stamp_empty_fill' => '#FFFFFF',
            'apple_stamp_empty_border' => '#FFFFFF',
            'apple_strip_overlay' => 0.55,

            'google_background' => '#8B5E3C',
            'google_stamp_filled' => '#FFFFFF',
            'google_stamp_empty' => '#1A1A1A',
            'google_stamp_border' => '#FFFFFF',
            'google_strip_bg' => '#000000',
            'google_strip_overlay' => 0.35,
            'google_stamp_text' => '#FFFFFF',

            'stamp_columns' => (int) config('apple-wallet.stamp_columns', 5),

            'apple_secondary_order' => ['rewards', 'remaining'],
            'apple_auxiliary_order' => ['member', 'status'],
            'apple_visible_fields' => ['rewards', 'remaining', 'member', 'status'],
            'google_modules_order' => ['remaining', 'status'],
            'google_visible_fields' => ['rewards', 'remaining', 'status'],

            'lang_ar' => trans('apple-google-wallet::wallet', [], 'ar'),
            'lang_en' => trans('apple-google-wallet::wallet', [], 'en'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function langKeys(): array
    {
        return [
            'stamps', 'rewards', 'remaining', 'member', 'status',
            'status_completed', 'status_in_progress', 'program', 'reward',
            'reward_description', 'card_code', 'loyalty_program', 'promo_message', 'barcode_footer',
        ];
    }

    /**
     * @return array<string, array<int, array{id: string, label: string}>>
     */
    public static function appleFieldSlots(): array
    {
        return [
            'secondary' => [
                ['id' => 'rewards', 'label' => 'المكافآت'],
                ['id' => 'remaining', 'label' => 'المتبقي'],
            ],
            'auxiliary' => [
                ['id' => 'member', 'label' => 'العضو'],
                ['id' => 'status', 'label' => 'الحالة'],
            ],
        ];
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    public static function googleFieldSlots(): array
    {
        return [
            ['id' => 'rewards', 'label' => 'المكافآت (REWARDS)'],
            ['id' => 'remaining', 'label' => 'المتبقي'],
            ['id' => 'status', 'label' => 'الحالة'],
        ];
    }
}
