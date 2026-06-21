<?php

return [
    'issuer_id' => env('GOOGLE_WALLET_ISSUER_ID'),
    'service_account_json' => env('GOOGLE_WALLET_SERVICE_ACCOUNT_JSON', storage_path('app/google-wallet/service-account.json')),
    'issuer_name' => env('GOOGLE_WALLET_ISSUER_NAME', env('APP_NAME', 'App')),
    'hex_background_color' => env('GOOGLE_WALLET_HEX_BACKGROUND', '#8B5E3C'),
    'default_logo' => env('GOOGLE_WALLET_DEFAULT_LOGO'),
    // Public HTTPS base used to rewrite local /storage URLs for Google Wallet (production CDN/domain)
    'public_asset_base_url' => env('GOOGLE_WALLET_PUBLIC_ASSET_BASE_URL'),
    // Used when local URLs (.test / localhost) cannot be fetched by Google servers
    'fallback_logo' => env('GOOGLE_WALLET_FALLBACK_LOGO'),

    // Public disk used for generated stamp strip images
    'public_disk' => env('GOOGLE_WALLET_PUBLIC_DISK', 'public'),
    'stamp_storage_path' => env('GOOGLE_WALLET_STAMP_STORAGE_PATH', 'wallet-stamps/google'),

    // Stamp card layout (Google Wallet uses LoyaltyClass with stamp-style fields)
    'card_layout' => env('GOOGLE_WALLET_CARD_LAYOUT', 'stamp'),
    'stamp_columns' => (int) env('GOOGLE_WALLET_STAMP_COLUMNS', 5),
    'stamp_cell_size' => (int) env('GOOGLE_WALLET_STAMP_CELL_SIZE', 88),
    'stamp_gap' => (int) env('GOOGLE_WALLET_STAMP_GAP', 20),
    'stamp_wallet_width' => (int) env('GOOGLE_WALLET_STAMP_WALLET_WIDTH', 1032),
    // PNG icons: completed wash vs empty wash (transparent background recommended)
    'stamp_completed_icon' => env('GOOGLE_WALLET_STAMP_COMPLETED_ICON'),
    'stamp_empty_icon' => env('GOOGLE_WALLET_STAMP_EMPTY_ICON'),
    'stamp_strip_background' => env('GOOGLE_WALLET_STAMP_STRIP_BG', '#000000'),
    // Optional image background for the stamp strip (falls back to stamp_strip_background color)
    'stamp_strip_background_image' => env('GOOGLE_WALLET_STAMP_STRIP_BG_IMAGE', '/images/stamps/STAMP_BG.jpeg'),
    'stamp_strip_background_overlay' => (float) env('GOOGLE_WALLET_STAMP_STRIP_BG_OVERLAY', 0.35),
    'stamp_filled_color' => env('GOOGLE_WALLET_STAMP_FILLED', '#FFFFFF'),
    'stamp_empty_color' => env('GOOGLE_WALLET_STAMP_EMPTY', '#1A1A1A'),
    'stamp_text_color' => env('GOOGLE_WALLET_STAMP_TEXT', '#FFFFFF'),
    'stamp_border_color' => env('GOOGLE_WALLET_STAMP_BORDER', '#FFFFFF'),

    // Override the default Google Wallet payload builder
    'payload_builder' => \Yacoubalhaidari\AppleGoogleWallet\Google\DefaultGoogleLoyaltyPayloadBuilder::class,
];
