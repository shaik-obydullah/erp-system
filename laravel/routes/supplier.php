<?php

use App\Http\Controllers\Supplier\Auth\SupplierNewPasswordController;
use App\Http\Controllers\Supplier\Auth\SupplierPasswordResetLinkController;
use App\Http\Controllers\Supplier\Auth\SupplierAuthenticatedSessionController;
use App\Http\Controllers\Supplier\SupplierDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('supplier')->group(function () {
    Route::middleware('guest:supplier')->group(function () {
        Route::get('login', [SupplierAuthenticatedSessionController::class, 'create'])
            ->name('supplier.login');

        Route::post('login', [SupplierAuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [SupplierPasswordResetLinkController::class, 'create'])
            ->name('supplier.password.request');

        Route::post('forgot-password', [SupplierPasswordResetLinkController::class, 'store'])
            ->name('supplier.password.email');

        Route::get('reset-password/{token}', [SupplierNewPasswordController::class, 'create'])
            ->name('supplier.password.reset');

        Route::post('reset-password', [SupplierNewPasswordController::class, 'store'])
            ->name('supplier.password.store');
    });

    Route::middleware('auth:supplier', 'active.supplier')->group(function () {
        Route::get('dashboard', [SupplierDashboardController::class, 'index'])
            ->name('supplier.dashboard');

        Route::post('logout', [SupplierAuthenticatedSessionController::class, 'destroy'])
            ->name('supplier.logout');
    });
});
