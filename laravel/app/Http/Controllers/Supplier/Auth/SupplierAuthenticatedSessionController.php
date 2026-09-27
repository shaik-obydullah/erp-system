<?php

namespace App\Http\Controllers\Supplier\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SupplierLoginRequest;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupplierAuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('supplier.auth.login');
    }

    public function store(SupplierLoginRequest $request): RedirectResponse|JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        ActivityService::success('Supplier logged in: '.Auth::guard('supplier')->user()->email);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => route('supplier.dashboard', absolute: false),
            ]);
        }

        return redirect()->intended(route('supplier.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        ActivityService::success('Supplier logged out: '.Auth::guard('supplier')->user()->email);

        Auth::guard('supplier')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
