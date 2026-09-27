<?php

namespace App\Http\Middleware;

use App\Models\Configuration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceModeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Configuration::get('maintenance_mode', '0') !== '1') {
            return $next($request);
        }

        if (auth('admin')->check() && auth('admin')->user()->hasRole('super-admin')) {
            return $next($request);
        }

        if (auth('customer')->check()) {
            return response()->view('errors.503', [], 503);
        }

        if (auth('supplier')->check()) {
            return response()->view('errors.503', [], 503);
        }

        return response()->view('errors.503', [], 503);
    }
}
