<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin || ! $admin->hasAnyRole($roles)) {
            abort(403, 'Unauthorized. Required role: '.implode(' or ', $roles));
        }

        return $next($request);
    }
}
