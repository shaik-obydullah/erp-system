<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin || ! $admin->hasAnyPermission($permissions)) {
            abort(403, 'Unauthorized. Required permission: '.implode(' or ', $permissions));
        }

        return $next($request);
    }
}
