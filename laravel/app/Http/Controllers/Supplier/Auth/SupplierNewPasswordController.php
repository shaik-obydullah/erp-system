<?php

namespace App\Http\Controllers\Supplier\Auth;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class SupplierNewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('supplier.auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::broker('suppliers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Supplier $supplier) use ($request) {
                $supplier->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($supplier));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? redirect()->route('supplier.login')->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
