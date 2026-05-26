<?php

return [
    'base_url'    => env('PRONET_BASE_URL', 'https://api.pronetgaming.eu'),
    'api_user'    => env('PRONET_API_USERNAME', ''),
    'secret'      => env('PRONET_SECRET', ''),
    'verify_ssl'  => env('PRONET_VERIFY_SSL', false),
    'code'        => '1830',
    'username'    => 'onurbonus',
    'password'    => 'Cas23400!!*',

    'bonus_history_url'     => env('BONUS_HISTORY_URL', 'http://127.0.0.1:5001'),
    'bonus_history_timeout' => (int) env('BONUS_HISTORY_TIMEOUT', 30),
];
