<?php

namespace App\Http\Middleware;

use App\Models\Supplier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ActiveSupplierMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('supplier')->user();

        if ($user instanceof Supplier && ! $user->isActive()) {
            Auth::guard('supplier')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('supplier.login')->withErrors([
                'email' => 'Your account has been deactivated.',
            ]);
        }

        return $next($request);
    }
}
