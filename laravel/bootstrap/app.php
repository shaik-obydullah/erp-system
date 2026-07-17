<?php

use App\Http\Middleware\ActiveAdminMiddleware;
use App\Http\Middleware\ActiveCustomerMiddleware;
use App\Http\Middleware\ActiveSupplierMiddleware;
use App\Http\Middleware\MaintenanceModeMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/customer.php'));

            Route::middleware('web')
                ->group(base_path('routes/supplier.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'active.admin' => ActiveAdminMiddleware::class,
            'active.customer' => ActiveCustomerMiddleware::class,
            'active.supplier' => ActiveSupplierMiddleware::class,
            'maintenance' => MaintenanceModeMiddleware::class,
        ]);
        $middleware->prependToGroup('web', MaintenanceModeMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
