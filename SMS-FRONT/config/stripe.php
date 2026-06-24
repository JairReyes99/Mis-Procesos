<?php

return [
    'secret'         => env('STRIPE_SECRET_KEY'),
    'publishable'    => env('STRIPE_PUBLISHABLE_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'api_version'    => '2025-04-30.basil',
    'currency'       => env('STRIPE_CURRENCY', 'mxn'),
];
