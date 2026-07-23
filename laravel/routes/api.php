<?php

use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [ApiController::class, 'login']);

// Public AI endpoints for storefront
Route::get('/ai/status', [AiController::class, 'status']);
Route::post('/ai/customer-support', [AiController::class, 'customerSupport']);

// Protected routes (Sanctum auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ApiController::class, 'logout']);
    Route::post('/configuration', [ApiController::class, 'configuration']);
    
    // Dashboard
    Route::post('/dashboard', [ApiController::class, 'dashboard']);
    Route::post('/category-product/{id}', [ApiController::class, 'categoryProduct']);
    
    // Customers
    Route::post('/customer', [ApiController::class, 'customer']);
    Route::post('/save-customer', [ApiController::class, 'saveCustomer']);
    Route::put('/update-customer/{id}', [ApiController::class, 'updateCustomer']);
    Route::delete('/delete-customer/{id}', [ApiController::class, 'deleteCustomer']);
    
    // Categories
    Route::post('/category', [ApiController::class, 'category']);
    Route::post('/save-category', [ApiController::class, 'saveCategory']);
    Route::put('/update-category/{id}', [ApiController::class, 'updateCategory']);
    Route::delete('/delete-category/{id}', [ApiController::class, 'deleteCategory']);
    
    // Products
    Route::get('/product', [ApiController::class, 'product']);
    Route::post('/save-product', [ApiController::class, 'saveProduct']);
    Route::post('/update-product/{id}', [ApiController::class, 'updateProduct']);
    Route::delete('/delete-product/{id}', [ApiController::class, 'deleteProduct']);
    
    // Stocks
    Route::get('/stock', [ApiController::class, 'stock']);
    Route::post('/save-stock', [ApiController::class, 'saveStock']);
    
    // Sales
    Route::get('/sale', [ApiController::class, 'sale']);
    Route::post('/select-sale/{id}', [ApiController::class, 'selectSale']);
    Route::delete('/delete-sale/{id}', [ApiController::class, 'deleteSale']);
    Route::post('/save-sale', [ApiController::class, 'saveSale']);
    
    // Incomes
    Route::post('/income', [ApiController::class, 'income']);
    Route::post('/save-income', [ApiController::class, 'saveIncome']);
    
    // Expenses
    Route::post('/expense', [ApiController::class, 'expense']);
    Route::post('/save-expense', [ApiController::class, 'saveExpense']);
    
    // Reports
    Route::post('/report', [ApiController::class, 'report']);

    // AI Features (Ollama) - Protected routes
    Route::post('/ai/product-description', [AiController::class, 'productDescription']);
    Route::post('/ai/product-search', [AiController::class, 'productSearch']);
    Route::post('/ai/inventory-insights', [AiController::class, 'inventoryInsights']);
    Route::post('/ai/sales-forecast', [AiController::class, 'salesForecast']);
    Route::post('/ai/price-suggestion', [AiController::class, 'priceSuggestion']);
});