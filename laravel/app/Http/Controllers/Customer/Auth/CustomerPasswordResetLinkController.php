<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerPasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('customer.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::broker('customers')->sendResetLink(
            $request->only('email')
        );

        if ($request->expectsJson()) {
            return $status == Password::RESET_LINK_SENT
                ? response()->json(['status' => __($status), 'redirect' => '/customer/forgot-password'])
                : response()->json(['errors' => ['email' => [__($status)]]], 422);
        }

        return $status == Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
