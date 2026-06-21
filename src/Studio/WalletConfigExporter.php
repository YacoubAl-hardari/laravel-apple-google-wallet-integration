<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Studio;

use Yacoubalhaidari\AppleGoogleWallet\Support\AppleColorNormalizer;

class WalletConfigExporter
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function export(array $input): array
    {
        $files = [];
        $platform = (string) ($input['platform'] ?? 'both');

        if (in_array($platform, ['apple', 'both'], true)) {
            $files['apple-wallet.php'] = $this->appleConfig($input);
        }

        if (in_array($platform, ['google', 'both'], true)) {
            $files['google-wallet.php'] = $this->googleConfig($input);
        }

        $files['lang/ar/wallet.php'] = $this->langFile($input['lang_ar'] ?? []);
        $files['lang/en/wallet.php'] = $this->langFile($input['lang_en'] ?? []);
        $files['.env.wallet'] = $this->envSnippet($input, $platform);

        return $files;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function appleConfig(array $input): string
    {
        $lines = array_merge([
            "<?php\n",
            'return [',
            "    'foreground_color' => env('APPLE_WALLET_FOREGROUND_COLOR', '" . $this->hexToRgbString($input['apple_foreground'] ?? '#FFFFFF') . "'),",
            "    'background_color' => env('APPLE_WALLET_BACKGROUND_COLOR', '" . $this->hexToRgbString($input['apple_background'] ?? '#8B5E3C') . "'),",
            "    'label_color' => env('APPLE_WALLET_LABEL_COLOR', '" . $this->hexToRgbString($input['apple_label'] ?? '#FFFFFF') . "'),",
            "    'stamp_columns' => (int) env('APPLE_WALLET_STAMP_COLUMNS', " . (int) ($input['stamp_columns'] ?? 5) . '),',
            "    'stamp_completed_color' => env('APPLE_WALLET_STAMP_COMPLETED_COLOR', '" . $this->hex($input['apple_stamp_completed'] ?? '#E07B2D') . "'),",
            "    'stamp_empty_fill' => env('APPLE_WALLET_STAMP_EMPTY_FILL', '" . $this->hex($input['apple_stamp_empty_fill'] ?? '#FFFFFF') . "'),",
            "    'stamp_empty_border' => env('APPLE_WALLET_STAMP_EMPTY_BORDER', '" . $this->hex($input['apple_stamp_empty_border'] ?? '#FFFFFF') . "'),",
            "    'strip_background_overlay' => (float) env('APPLE_WALLET_STRIP_BG_OVERLAY', " . (float) ($input['apple_strip_overlay'] ?? 0.55) . '),',
        ], $this->appleFieldsBlock($input), $this->appleImagesBlock($input), [
            '];',
        ]);

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function googleConfig(array $input): string
    {
        $lines = array_merge([
            "<?php\n",
            'return [',
            "    'hex_background_color' => env('GOOGLE_WALLET_HEX_BACKGROUND', '" . $this->hex($input['google_background'] ?? '#8B5E3C') . "'),",
            "    'stamp_columns' => (int) env('GOOGLE_WALLET_STAMP_COLUMNS', " . (int) ($input['stamp_columns'] ?? 5) . '),',
            "    'stamp_strip_background' => env('GOOGLE_WALLET_STAMP_STRIP_BG', '" . $this->hex($input['google_strip_bg'] ?? '#000000') . "'),",
            "    'stamp_strip_background_overlay' => (float) env('GOOGLE_WALLET_STAMP_STRIP_BG_OVERLAY', " . (float) ($input['google_strip_overlay'] ?? 0.35) . '),',
            "    'stamp_filled_color' => env('GOOGLE_WALLET_STAMP_FILLED', '" . $this->hex($input['google_stamp_filled'] ?? '#FFFFFF') . "'),",
            "    'stamp_empty_color' => env('GOOGLE_WALLET_STAMP_EMPTY', '" . $this->hex($input['google_stamp_empty'] ?? '#1A1A1A') . "'),",
            "    'stamp_border_color' => env('GOOGLE_WALLET_STAMP_BORDER', '" . $this->hex($input['google_stamp_border'] ?? '#FFFFFF') . "'),",
            "    'stamp_text_color' => env('GOOGLE_WALLET_STAMP_TEXT', '" . $this->hex($input['google_stamp_text'] ?? '#FFFFFF') . "'),",
        ], $this->googleFieldsBlock($input), $this->googleImagesBlock($input), [
            '];',
        ]);

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param  array<string, string>  $labels
     */
    protected function langFile(array $labels): string
    {
        $lines = ["<?php\n", 'return ['];

        foreach ($this->langKeys() as $key) {
            $value = str_replace("'", "\\'", (string) ($labels[$key] ?? ''));
            $lines[] = "    '{$key}' => '{$value}',";
        }

        $lines[] = '];';

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function envSnippet(array $input, string $platform): string
    {
        $lines = ['# Wallet Design Studio export', 'WALLET_LOCALE=' . ($input['preview_locale'] ?? 'ar'), ''];

        if (in_array($platform, ['apple', 'both'], true)) {
            $lines[] = '# Apple Wallet';
            $lines[] = 'APPLE_WALLET_FOREGROUND_COLOR=' . $this->hexToRgbString($input['apple_foreground'] ?? '#FFFFFF');
            $lines[] = 'APPLE_WALLET_BACKGROUND_COLOR=' . $this->hexToRgbString($input['apple_background'] ?? '#8B5E3C');
            $lines[] = 'APPLE_WALLET_LABEL_COLOR=' . $this->hexToRgbString($input['apple_label'] ?? '#FFFFFF');
            $lines[] = 'APPLE_WALLET_STAMP_COLUMNS=' . (int) ($input['stamp_columns'] ?? 5);
            $lines[] = 'APPLE_WALLET_STAMP_COMPLETED_COLOR=' . $this->hex($input['apple_stamp_completed'] ?? '#E07B2D');
            $lines[] = 'APPLE_WALLET_STAMP_EMPTY_FILL=' . $this->hex($input['apple_stamp_empty_fill'] ?? '#FFFFFF');
            $lines[] = 'APPLE_WALLET_STAMP_EMPTY_BORDER=' . $this->hex($input['apple_stamp_empty_border'] ?? '#FFFFFF');
            $lines[] = 'APPLE_WALLET_STRIP_BG_OVERLAY=' . (float) ($input['apple_strip_overlay'] ?? 0.55);
            $lines[] = '';
        }

        if (in_array($platform, ['google', 'both'], true)) {
            $lines[] = '# Google Wallet';
            $lines[] = 'GOOGLE_WALLET_HEX_BACKGROUND=' . $this->hex($input['google_background'] ?? '#8B5E3C');
            $lines[] = 'GOOGLE_WALLET_STAMP_COLUMNS=' . (int) ($input['stamp_columns'] ?? 5);
            $lines[] = 'GOOGLE_WALLET_STAMP_STRIP_BG=' . $this->hex($input['google_strip_bg'] ?? '#000000');
            $lines[] = 'GOOGLE_WALLET_STAMP_STRIP_BG_OVERLAY=' . (float) ($input['google_strip_overlay'] ?? 0.35);
            $lines[] = 'GOOGLE_WALLET_STAMP_FILLED=' . $this->hex($input['google_stamp_filled'] ?? '#FFFFFF');
            $lines[] = 'GOOGLE_WALLET_STAMP_EMPTY=' . $this->hex($input['google_stamp_empty'] ?? '#1A1A1A');
            $lines[] = 'GOOGLE_WALLET_STAMP_BORDER=' . $this->hex($input['google_stamp_border'] ?? '#FFFFFF');
            $lines[] = 'GOOGLE_WALLET_STAMP_TEXT=' . $this->hex($input['google_stamp_text'] ?? '#FFFFFF');
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @return array<int, string>
     */
    protected function langKeys(): array
    {
        return [
            'stamps', 'rewards', 'remaining', 'member', 'status',
            'status_completed', 'status_in_progress', 'program', 'reward',
            'reward_description', 'card_code', 'loyalty_program', 'promo_message',
            'barcode_footer', 'google_logo_required',
        ];
    }

    protected function hex(mixed $color): string
    {
        $color = trim((string) $color);
        if ($color === '') {
            return '#000000';
        }

        return str_starts_with($color, '#') ? strtoupper($color) : '#' . strtoupper($color);
    }

    protected function hexToRgbString(mixed $color): string
    {
        return AppleColorNormalizer::normalize($this->hex($color), 'rgb(255, 255, 255)');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, string>
     */
    protected function appleFieldsBlock(array $input): array
    {
        $visible = $this->phpArray($input['apple_visible_fields'] ?? ['rewards', 'remaining', 'member', 'status']);
        $secondary = $this->phpArray($input['apple_secondary_order'] ?? ['rewards', 'remaining']);
        $auxiliary = $this->phpArray($input['apple_auxiliary_order'] ?? ['member', 'status']);

        return [
            "    'fields' => [",
            "        'visible' => {$visible},",
            "        'secondary' => {$secondary},",
            "        'auxiliary' => {$auxiliary},",
            '    ],',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, string>
     */
    protected function googleFieldsBlock(array $input): array
    {
        $visible = $this->phpArray($input['google_visible_fields'] ?? ['rewards', 'remaining', 'status']);
        $modules = $this->phpArray($input['google_modules_order'] ?? ['remaining', 'status']);

        return [
            "    'fields' => [",
            "        'visible' => {$visible},",
            "        'modules' => {$modules},",
            '    ],',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, string>
     */
    protected function appleImagesBlock(array $input): array
    {
        $lines = [];

        if (! empty($input['logo_path'])) {
            $path = storage_path('app/public/' . ltrim((string) $input['logo_path'], '/'));
            $lines[] = "    'icon_path' => env('APPLE_WALLET_ICON_PATH', '" . str_replace('\\', '/', $path) . "'),";
            $lines[] = "    'logo_path' => env('APPLE_WALLET_LOGO_PATH', '" . str_replace('\\', '/', $path) . "'),";
        }

        if (! empty($input['strip_bg_path'])) {
            $lines[] = "    'strip_background_image' => env('APPLE_WALLET_STRIP_BG_IMAGE', '/storage/" . ltrim((string) $input['strip_bg_path'], '/') . "'),";
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, string>
     */
    protected function googleImagesBlock(array $input): array
    {
        $lines = [];

        if (! empty($input['logo_url'])) {
            $lines[] = "    'default_logo' => env('GOOGLE_WALLET_DEFAULT_LOGO', '" . addslashes((string) $input['logo_url']) . "'),";
        }

        if (! empty($input['strip_bg_path'])) {
            $lines[] = "    'stamp_strip_background_image' => env('GOOGLE_WALLET_STAMP_STRIP_BG_IMAGE', '/storage/" . ltrim((string) $input['strip_bg_path'], '/') . "'),";
        }

        return $lines;
    }

    /**
     * @param  array<int, string>|mixed  $values
     */
    protected function phpArray(mixed $values): string
    {
        if (! is_array($values)) {
            return '[]';
        }

        $items = array_map(fn ($v) => "'" . addslashes((string) $v) . "'", array_values($values));

        return '[' . implode(', ', $items) . ']';
    }
}
