<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CustomerLoginRequest;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerAuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('customer.auth.login');
    }

    public function store(CustomerLoginRequest $request): RedirectResponse|JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        ActivityService::success('Customer logged in: '.Auth::guard('customer')->user()->email);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => route('customer.dashboard', absolute: false),
            ]);
        }

        return redirect()->intended(route('customer.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        ActivityService::success('Customer logged out: '.Auth::guard('customer')->user()->email);

        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
