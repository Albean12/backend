<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/login-admin-tenant',
        'api/logout',
        'api/register',
        'sanctum/csrf-cookie',
        'api/auth/validate-token',
        'api/applications/*/decline',
        'api/upload-id',
        'api/units/*/status',
        'api/maintenance-requests/*/update',
        'api/events',           // Added explicit route
        'api/events/*',         // Added wildcard for nested routes
        'api/applications',     // Added to cover all application routes
        'api/applications/*'    // Wildcard for nested application routes
    ];
}
