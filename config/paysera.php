<?php

declare(strict_types=1);

return [

    'project_id' => env('PAYSERA_PROJECT_ID'),

    'sign_password' => env('PAYSERA_SIGN_PASSWORD'),

    'currency' => env('PAYSERA_CURRENCY', 'EUR'),

    'test_mode' => (bool) env('PAYSERA_TEST_MODE', true),

    'urls' => [
        'accept_url' => env('PAYSERA_ACCEPT_URL'),
        'cancel_url' => env('PAYSERA_CANCEL_URL'),
        'callback_url' => env('PAYSERA_CALLBACK_URL'),
    ],

    'defaults' => [
        'country' => env('PAYSERA_COUNTRY'),
        'language' => env('PAYSERA_LANGUAGE'),
        'payment_method' => env('PAYSERA_PAYMENT_METHOD'),
    ],

    'routes' => [
        'enabled' => (bool) env('PAYSERA_ROUTES_ENABLED', true),
        'prefix' => env('PAYSERA_ROUTES_PREFIX', ''),
        'middleware' => env('PAYSERA_ROUTES_MIDDLEWARE', 'web'),
    ],

    'api' => [
        'enabled' => (bool) env('PAYSERA_API_MODE', false),
    ],

];
