<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'https://sea-gold-dormitory.vercel.app' // ✅ Added Vercel frontend
    ],
    'allowed_origins_patterns' => [], // ✅ Supports dynamic subdomains if needed
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Authorization'],
    'max_age' => 0,
    'supports_credentials' => true, // ✅ Required for CSRF and Authentication
];

