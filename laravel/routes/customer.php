<?php

use App\Http\Controllers\Customer\Auth\CustomerNewPasswordController;
use App\Http\Controllers\Customer\Auth\CustomerPasswordResetLinkController;
use App\Http\Controllers\Customer\Auth\CustomerAuthenticatedSessionController;
use App\Http\Controllers\Customer\Auth\RegisteredCustomerController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\CustomerProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('customer')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('register', [RegisteredCustomerController::class, 'create'])
            ->name('customer.register');

        Route::post('register', [RegisteredCustomerController::class, 'store']);

        Route::get('login', [CustomerAuthenticatedSessionController::class, 'create'])
            ->name('customer.login');

        Route::post('login', [CustomerAuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [CustomerPasswordResetLinkController::class, 'create'])
            ->name('customer.password.request');

        Route::post('forgot-password', [CustomerPasswordResetLinkController::class, 'store'])
            ->name('customer.password.email');

        Route::get('reset-password/{token}', [CustomerNewPasswordController::class, 'create'])
            ->name('customer.password.reset');

        Route::post('reset-password', [CustomerNewPasswordController::class, 'store'])
            ->name('customer.password.store');
    });

    Route::middleware('auth:customer', 'active.customer')->group(function () {
        Route::get('dashboard', [CustomerDashboardController::class, 'index'])
            ->name('customer.dashboard');

        Route::get('profile', [CustomerProfileController::class, 'edit'])
            ->name('customer.profile.edit');

        Route::put('profile', [CustomerProfileController::class, 'update'])
            ->name('customer.profile.update');

        Route::put('password', [CustomerProfileController::class, 'updatePassword'])
            ->name('customer.password.update');

        Route::post('logout', [CustomerAuthenticatedSessionController::class, 'destroy'])
            ->name('customer.logout');
    });
});
