<?php

return [
    'enabled' => env('WALLET_STUDIO_ENABLED', env('APP_ENV') === 'local'),
    'route_prefix' => env('WALLET_STUDIO_ROUTE', 'wallet-studio'),
    'middleware' => ['web'],
];
