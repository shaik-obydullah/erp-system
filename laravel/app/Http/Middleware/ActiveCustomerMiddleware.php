<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ActiveCustomerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('customer')->user();

        if ($user instanceof Customer && ! $user->isActive()) {
            Auth::guard('customer')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('customer.login')->withErrors([
                'email' => 'Your account has been deactivated.',
            ]);
        }

        return $next($request);
    }
}
