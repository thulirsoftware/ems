<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        'api/v1/*',
        'web/v1/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000', // CRA
        'http://localhost:5173', // Vite
        'http://localhost:5174', // Vite
        'http://localhost:5175', // Vite
        'http://localhost:5176', // Vite
        'http://localhost:8080', // Next / custom
        'http://127.0.0.1:3000',
        'https://seashell-okapi-169452.hostingersite.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
