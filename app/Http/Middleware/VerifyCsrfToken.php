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
        'api/login-admin-tenant', // ✅ Exclude the correct login route
        'api/logout',             // ✅ Exclude logout if needed
        'api/register',           // ✅ If you have user registration
        'sanctum/csrf-cookie',    // ✅ CSRF token endpoint
        'api/auth/validate-token',  // ✅ Fix validate-token 419 error
        'api/applications/*/decline', // Exempt this route from CSRF protection
        'api/upload-id',  // ✅ Exempt this route from CSRF protection
    ];
}
