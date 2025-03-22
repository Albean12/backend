<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        if ($request->getMethod() === 'OPTIONS') {
            return response()->json('OK', 200)
                ->header('Access-Control-Allow-Origin', 'https://sea-gold-dormitory.vercel.app')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');
        }

        $response = $next($request);

        return $response
            ->header('Access-Control-Allow-Origin', 'https://sea-gold-dormitory.vercel.app')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');
    }
}
