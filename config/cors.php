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

    'paths' => ['api/*', 'storage/app/public/*', 'docs/*', 'api/documentation'],
    
    'allowed_methods' => env('CORS_ALLOWED_METHODS') 
        ? explode(',', env('CORS_ALLOWED_METHODS'))
        : ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
    
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS')
        ? explode(',', env('CORS_ALLOWED_ORIGINS'))
        : [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:4000',
            'http://localhost:8000',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'http://127.0.0.1:4000',
            'http://127.0.0.1:8000',
        ],
    
    'allowed_origins_patterns' => [
        '/^http:\/\/localhost:\d+$/',
        '/^http:\/\/127\.0\.0\.1:\d+$/',
    ],
    
    'allowed_headers' => env('CORS_ALLOWED_HEADERS')
        ? explode(',', env('CORS_ALLOWED_HEADERS'))
        : [
            'Accept',
            'Authorization',
            'Content-Type',
            'X-Requested-With',
            'X-CSRF-TOKEN',
            'Origin',
            'Access-Control-Request-Method',
            'Access-Control-Request-Headers',
        ],
    
    'exposed_headers' => [],
    
    'max_age' => env('CORS_MAX_AGE', 86400),
    
    'supports_credentials' => env('CORS_ALLOW_CREDENTIALS', true),
];
