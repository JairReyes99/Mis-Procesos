<?php

return [
    'client_id'     => env('PAYPAL_CLIENT_ID'),
    'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    'webhook_id'    => env('PAYPAL_WEBHOOK_ID'),
    'mode'          => env('PAYPAL_MODE', 'sandbox'),
    'currency'      => env('PAYPAL_CURRENCY', 'MXN'),
    'sandbox_url'   => 'https://api-m.sandbox.paypal.com',
    'live_url'      => 'https://api-m.paypal.com',
];
