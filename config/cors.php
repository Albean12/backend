<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'https://sea-gold-dormitory.vercel.app'
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Authorization', 'XSRF-TOKEN'], // ✅ Expose CSRF Token
    'max_age' => 0,
    'supports_credentials' => true,
];
