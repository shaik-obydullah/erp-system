<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerDueController;
use App\Http\Controllers\CustomerFundController;
use App\Http\Controllers\CustomerTransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierFundController;
use App\Http\Controllers\SupplierDueController;
use App\Http\Controllers\SupplierTransactionController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\NeedController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\CashbookController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ShipmentReturnController;
use App\Http\Controllers\BillOfMaterialController;
use App\Http\Controllers\ProductionPlanningController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CmsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('store.home');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth:admin', 'active.admin', 'verified', 'permission:dashboard.view'])
    ->name('dashboard');

Route::middleware(['auth:admin', 'active.admin'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Roles
    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view')
        ->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])
        ->middleware('permission:roles.save')
        ->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.save')
        ->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('permission:roles.edit')
        ->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.edit')
        ->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete')
        ->name('roles.destroy');

    // Admins
    Route::get('/admins', [AdminController::class, 'index'])
        ->middleware('permission:admins.view')
        ->name('admins.index');
    Route::get('/admins/create', [AdminController::class, 'create'])
        ->middleware('permission:admins.save')
        ->name('admins.create');
    Route::post('/admins', [AdminController::class, 'store'])
        ->middleware('permission:admins.save')
        ->name('admins.store');
    Route::get('/admins/{admin}/edit', [AdminController::class, 'edit'])
        ->middleware('permission:admins.edit')
        ->name('admins.edit');
    Route::put('/admins/{admin}', [AdminController::class, 'update'])
        ->middleware('permission:admins.edit')
        ->name('admins.update');
    Route::delete('/admins/{admin}', [AdminController::class, 'destroy'])
        ->middleware('permission:admins.delete')
        ->name('admins.destroy');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])
        ->middleware('permission:customers.view')
        ->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])
        ->middleware('permission:customers.save')
        ->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])
        ->middleware('permission:customers.save')
        ->name('customers.store');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])
        ->middleware('permission:customers.edit')
        ->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('permission:customers.edit')
        ->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('permission:customers.delete')
        ->name('customers.destroy');

    // Customer Fund
    Route::get('/customers/fund', [CustomerFundController::class, 'index'])
        ->middleware('permission:customers.view')
        ->name('customers.fund.index');
    Route::post('/customers/fund', [CustomerFundController::class, 'store'])
        ->middleware('permission:customers.save')
        ->name('customers.fund.store');
    Route::get('/customers/fund/balance', [CustomerFundController::class, 'balance'])
        ->middleware('permission:customers.view')
        ->name('customers.fund.balance');

    // Customer Due
    Route::get('/customers/due', [CustomerDueController::class, 'index'])
        ->middleware('permission:customers.view')
        ->name('customers.due.index');

    // Customer Transactions
    Route::get('/customers/transactions', [CustomerTransactionController::class, 'index'])
        ->middleware('permission:customers.view')
        ->name('customers.transaction.index');

    // Customer Import/Export
    Route::get('/customers/export', [CustomerController::class, 'export'])
        ->middleware('permission:customers.view')
        ->name('customers.export');
    Route::post('/customers/import', [CustomerController::class, 'import'])
        ->middleware('permission:customers.save')
        ->name('customers.import');

    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->middleware('permission:suppliers.view')
        ->name('suppliers.index');
    Route::get('/suppliers/create', [SupplierController::class, 'create'])
        ->middleware('permission:suppliers.save')
        ->name('suppliers.create');
    Route::post('/suppliers', [SupplierController::class, 'store'])
        ->middleware('permission:suppliers.save')
        ->name('suppliers.store');
    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])
        ->middleware('permission:suppliers.edit')
        ->name('suppliers.edit');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])
        ->middleware('permission:suppliers.edit')
        ->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:suppliers.delete')
        ->name('suppliers.destroy');

    // Supplier Fund
    Route::get('/suppliers/fund', [SupplierFundController::class, 'index'])
        ->middleware('permission:suppliers.view')
        ->name('suppliers.fund.index');
    Route::post('/suppliers/fund', [SupplierFundController::class, 'store'])
        ->middleware('permission:suppliers.save')
        ->name('suppliers.fund.store');
    Route::get('/suppliers/fund/balance', [SupplierFundController::class, 'balance'])
        ->middleware('permission:suppliers.view')
        ->name('suppliers.fund.balance');

    // Supplier Due
    Route::get('/suppliers/due', [SupplierDueController::class, 'index'])
        ->middleware('permission:suppliers.view')
        ->name('suppliers.due.index');

    // Supplier Transactions
    Route::get('/suppliers/transactions', [SupplierTransactionController::class, 'index'])
        ->middleware('permission:suppliers.view')
        ->name('suppliers.transaction.index');

    // Supplier Import/Export
    Route::get('/suppliers/export', [SupplierController::class, 'export'])
        ->middleware('permission:suppliers.view')
        ->name('suppliers.export');
    Route::post('/suppliers/import', [SupplierController::class, 'import'])
        ->middleware('permission:suppliers.save')
        ->name('suppliers.import');

    // Brands
    Route::resource('brands', BrandController::class)->except(['show']);

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Units
    Route::resource('units', UnitController::class)->except(['show']);

    // Sizes
    Route::resource('sizes', SizeController::class)->except(['show']);

    // Colors
    Route::resource('colors', ColorController::class)->except(['show']);

    // Products
    Route::resource('products', ProductController::class)->except(['show']);

    // Stocks
    Route::get('/stocks', [StockController::class, 'index'])
        ->middleware('permission:stocks.view')
        ->name('stocks.index');
    Route::get('/stocks/create', [StockController::class, 'create'])
        ->middleware('permission:stocks.save')
        ->name('stocks.create');
    Route::post('/stocks', [StockController::class, 'store'])
        ->middleware('permission:stocks.save')
        ->name('stocks.store');
    Route::get('/stocks/{stock}/edit', [StockController::class, 'edit'])
        ->middleware('permission:stocks.edit')
        ->name('stocks.edit');
    Route::put('/stocks/{stock}', [StockController::class, 'update'])
        ->middleware('permission:stocks.edit')
        ->name('stocks.update');
    Route::delete('/stocks/{stock}', [StockController::class, 'destroy'])
        ->middleware('permission:stocks.delete')
        ->name('stocks.destroy');

    // Stock Adjustments
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])
        ->middleware('permission:stocks.view')
        ->name('stock-adjustments.index');
    Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])
        ->middleware('permission:stocks.save')
        ->name('stock-adjustments.create');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])
        ->middleware('permission:stocks.save')
        ->name('stock-adjustments.store');

    // Sales & POS
    Route::get('/pos', [PosController::class, 'index'])
        ->middleware('permission:pos.view')
        ->name('pos.index');
    Route::post('/pos/cart/add', [PosController::class, 'addToCart'])
        ->middleware('permission:pos.save')
        ->name('pos.cart.add');
    Route::post('/pos/cart/remove', [PosController::class, 'removeFromCart'])
        ->middleware('permission:pos.save')
        ->name('pos.cart.remove');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])
        ->middleware('permission:pos.save')
        ->name('pos.checkout');
    Route::get('/sales', [SaleController::class, 'index'])
        ->middleware('permission:sales.view')
        ->name('sales.index');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])
        ->middleware('permission:sales.view')
        ->name('sales.show');
    Route::get('/sales/{sale}/invoice', [SaleController::class, 'invoice'])
        ->middleware('permission:sales.view')
        ->name('sales.invoice');

    // Procurement - Needs
    Route::resource('needs', NeedController::class)->except(['show']);

    // Procurement - Purchase Orders
    Route::resource('purchase-orders', PurchaseOrderController::class)->except(['show']);

    // Finance - Cashbook
    Route::get('/cashbook', [CashbookController::class, 'index'])
        ->middleware('permission:cashbook.view')
        ->name('cashbook.index');

    // Finance - Income
    Route::get('/incomes', [IncomeController::class, 'index'])
        ->middleware('permission:incomes.view')
        ->name('incomes.index');
    Route::get('/incomes/create', [IncomeController::class, 'create'])
        ->middleware('permission:incomes.save')
        ->name('incomes.create');
    Route::post('/incomes', [IncomeController::class, 'store'])
        ->middleware('permission:incomes.save')
        ->name('incomes.store');

    // Finance - Expense
    Route::get('/expenses', [ExpenseController::class, 'index'])
        ->middleware('permission:expenses.view')
        ->name('expenses.index');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])
        ->middleware('permission:expenses.save')
        ->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])
        ->middleware('permission:expenses.save')
        ->name('expenses.store');

    // Finance - Payable
    Route::get('/payables', [PayableController::class, 'index'])
        ->middleware('permission:cashbook.view')
        ->name('payables.index');

    // Finance - Receivable
    Route::get('/receivables', [ReceivableController::class, 'index'])
        ->middleware('permission:cashbook.view')
        ->name('receivables.index');

    // Finance - Fixed Assets
    Route::resource('fixed-assets', FixedAssetController::class)->except(['show']);

    // Finance - Transaction Log
    Route::get('/transactions', [TransactionController::class, 'index'])
        ->middleware('permission:transactions.view')
        ->name('transactions.index');

    // HRM - Employees
    Route::resource('employees', EmployeeController::class)->except(['show']);

    // HRM - Payrolls
    Route::resource('payrolls', PayrollController::class)->except(['show']);

    // HRM - Tasks
    Route::resource('tasks', TaskController::class)->except(['show']);

    // Marketing - Campaigns
    Route::resource('campaigns', CampaignController::class)->except(['show']);

    // Logistics - Warehouses
    Route::resource('warehouses', WarehouseController::class)->except(['show']);

    // Logistics - Shipments
    Route::resource('shipments', ShipmentController::class)->except(['show']);

    // Logistics - Shipment Returns
    Route::resource('shipment-returns', ShipmentReturnController::class)->except(['show']);

    // Manufacturing - Bill of Materials
    Route::resource('bill-of-materials', BillOfMaterialController::class)->except(['show']);

    // Manufacturing - Production Planning
    Route::resource('production-plannings', ProductionPlanningController::class)->except(['show']);

    // Manufacturing - Production
    Route::resource('productions', ProductionController::class)->except(['show']);

    // CMS Content
    Route::resource('cms', CmsController::class)->except(['show']);

    // Permissions
    Route::get('/permissions', [PermissionController::class, 'index'])
        ->middleware('permission:roles.view')
        ->name('permissions.index');

    // Activity Log
    Route::get('/activities', [ActivityController::class, 'index'])
        ->middleware('permission:activity.view')
        ->name('activities.index');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    // ERP Settings
    Route::get('/settings', [ConfigController::class, 'settings'])
        ->middleware('permission:settings.view')
        ->name('settings.index');
    Route::put('/settings', [ConfigController::class, 'updateSettings'])
        ->middleware('permission:settings.save')
        ->name('settings.update');
    Route::post('/maintenance/toggle', [ConfigController::class, 'toggleMaintenance'])
        ->middleware('permission:settings.save')
        ->name('settings.maintenance.toggle');
});

// Storefront (Multi-Vendor Ecommerce) - after admin routes to avoid route conflicts
Route::get('/home', [StorefrontController::class, 'home'])->name('store.home');
Route::get('/products-list', [StorefrontController::class, 'products'])->name('store.products');
Route::get('/product/{slug}', [StorefrontController::class, 'productDetail'])->name('store.product');
Route::get('/vendors', [StorefrontController::class, 'vendorList'])->name('store.vendors');
Route::get('/vendor/{slug}', [StorefrontController::class, 'vendorStore'])->name('store.vendor');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('store.cart');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('store.checkout');

// API routes (Vue components)
Route::middleware(['auth:admin', 'active.admin'])->prefix('api')->group(function () {
    // Activity API
    Route::get('/activities', [ActivityController::class, 'api']);
    Route::get('/activities/stats', [ActivityController::class, 'stats']);
    Route::delete('/activities/clear', [ActivityController::class, 'clear']);

    // Notification API
    Route::get('/notifications', [NotificationController::class, 'api']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/notifications/{id}/seen', [NotificationController::class, 'markSeen']);
    Route::post('/notifications/mark-all-seen', [NotificationController::class, 'markAllSeen']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::post('/notifications', [NotificationController::class, 'store']);
});

require __DIR__.'/auth.php';
