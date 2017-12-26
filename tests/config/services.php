<?php
return [
    'sendgrid' => [
        'api_key'      => env('SENDGRID_API_KEY', ''),
        'sandbox_mode' => env('SENDGRID_SANDBOX_MODE', false),
    ]
];
