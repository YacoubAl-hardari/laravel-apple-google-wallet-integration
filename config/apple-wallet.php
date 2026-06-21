<?php

return [
    'pass_type_identifier' => env('APPLE_WALLET_PASS_TYPE_IDENTIFIER'),
    'team_identifier' => env('APPLE_WALLET_TEAM_IDENTIFIER'),
    'organization_name' => env('APPLE_WALLET_ORGANIZATION_NAME', env('APP_NAME', 'App')),
    'foreground_color' => env('APPLE_WALLET_FOREGROUND_COLOR', 'rgb(255, 255, 255)'),
    'background_color' => env('APPLE_WALLET_BACKGROUND_COLOR', 'rgb(139, 94, 60)'),
    'label_color' => env('APPLE_WALLET_LABEL_COLOR', 'rgb(255, 255, 255)'),
    'icon_path' => env('APPLE_WALLET_ICON_PATH', public_path('images/logo.png')),
    'logo_path' => env('APPLE_WALLET_LOGO_PATH', public_path('images/logo.png')),
    'barcode_alt_text' => env('APPLE_WALLET_BARCODE_ALT_TEXT', ' '),
    'signed_url_ttl_minutes' => (int) env('APPLE_WALLET_SIGNED_URL_TTL', 60),

    // Storage disk used while building .pkpass files
    'storage_disk' => env('APPLE_WALLET_STORAGE_DISK', 'apple-wallet'),
    // Public disk used for generated stamp strip images
    'public_disk' => env('APPLE_WALLET_PUBLIC_DISK', 'public'),
    'stamp_storage_path' => env('APPLE_WALLET_STAMP_STORAGE_PATH', 'wallet-stamps/apple'),

    // Strip image (@3x — Apple storeCard recommended 1125×369)
    'strip_width' => (int) env('APPLE_WALLET_STRIP_WIDTH', 1125),
    'strip_height' => (int) env('APPLE_WALLET_STRIP_HEIGHT', 369),
    'strip_background_image' => env('APPLE_WALLET_STRIP_BG_IMAGE', '/images/stamps/STAMP_BG.png'),
    'strip_background_overlay' => (float) env('APPLE_WALLET_STRIP_BG_OVERLAY', 0.55),
    'stamp_cell_size' => (int) env('APPLE_WALLET_STAMP_CELL_SIZE', 118),
    'stamp_gap' => (int) env('APPLE_WALLET_STAMP_GAP', 22),
    'stamp_columns' => (int) env('APPLE_WALLET_STAMP_COLUMNS', 5),
    'stamp_completed_color' => env('APPLE_WALLET_STAMP_COMPLETED_COLOR', '#E07B2D'),
    'stamp_empty_fill' => env('APPLE_WALLET_STAMP_EMPTY_FILL', '#FFFFFF'),
    'stamp_empty_border' => env('APPLE_WALLET_STAMP_EMPTY_BORDER', '#FFFFFF'),
    'stamp_border_width' => (int) env('APPLE_WALLET_STAMP_BORDER_WIDTH', 4),
    'stamp_completed_icon' => env('APPLE_WALLET_STAMP_COMPLETED_ICON'),
    'stamp_empty_icon' => env('APPLE_WALLET_STAMP_EMPTY_ICON'),

    'certificate_store_path' => env('APPLE_WALLET_CERTIFICATE_PATH', storage_path('app/apple-wallet/pass.p12')),
    'certificate_store_password' => env('APPLE_WALLET_CERTIFICATE_PASS', ''),
    'wwdr_certificate_path' => env('APPLE_WALLET_WWDR_CERTIFICATE', storage_path('app/apple-wallet/AppleWWDRCAG6.pem')),

    // Override the default pass definition builder
    'pass_definition_builder' => \Yacoubalhaidari\AppleGoogleWallet\Apple\DefaultApplePassDefinitionBuilder::class,
];
