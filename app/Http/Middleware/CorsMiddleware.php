<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        return $response
            ->header('Access-Control-Allow-Origin', 'https://sea-gold-dormitory.vercel.app') // ✅ Set specific frontend URL
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization, X-CSRF-TOKEN')
            ->header('Access-Control-Allow-Credentials', 'true'); // ✅ Allow sending credentials
    }
}
