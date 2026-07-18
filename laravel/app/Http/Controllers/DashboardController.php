<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');

        $totalSales = Sale::sum('grand_total');
        $totalRevenue = Sale::sum('paid_amount');
        $totalExpenses = Expense::sum('amount');
        $totalProducts = Product::where('status', 'active')->count();
        $totalCustomers = Customer::count();
        $totalSuppliers = Supplier::where('status', 'active')->count();
        $totalEmployees = Employee::where('status', 'active')->count();
        $totalStockValue = Stock::where('status', 'active')->sum(DB::raw('quantity * buy_price'));

        $totalOrders = Sale::count();
        $completedOrders = Sale::where('status', 'completed')->count();
        $pendingOrders = Sale::where('status', 'orderPlaced')->count();
        $totalDue = Sale::where('sale_due', '>', 0)->sum('sale_due');

        $recentSales = Sale::orderBy('id', 'desc')->limit(5)->get();

        $topProducts = Product::select('products.*')
            ->selectSub(function ($query) {
                $query->selectRaw('COALESCE(SUM(sale_details.sale_stock), 0)')
                    ->from('sale_details')
                    ->join('stocks', 'stocks.id', '=', 'sale_details.fk_stock_id')
                    ->join('sales', 'sales.id', '=', 'sale_details.fk_sale_id')
                    ->whereColumn('stocks.fk_product_id', 'products.id')
                    ->whereNull('sales.deleted_at');
            }, 'total_sold')
            ->where('products.status', 'active')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        if ($topProducts->isEmpty() || $topProducts->first()->total_sold == 0) {
            $topProducts = Product::where('status', 'active')
                ->withCount('stocks')
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get();
        }

        $monthlySales = [];
        $monthlyExpenses = [];

        $categoryRevenue = Product::select('categories.name as category_name')
            ->selectRaw('COALESCE(SUM(sale_details.subtotal), 0) as revenue')
            ->leftJoin('stocks', 'stocks.fk_product_id', '=', 'products.id')
            ->leftJoin('sale_details', 'sale_details.fk_stock_id', '=', 'stocks.id')
            ->leftJoin('categories', 'categories.id', '=', 'products.fk_category_id')
            ->where('products.status', 'active')
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'currencySymbol',
            'totalSales', 'totalRevenue', 'totalExpenses', 'totalProducts',
            'totalCustomers', 'totalSuppliers', 'totalEmployees', 'totalStockValue',
            'totalOrders', 'completedOrders', 'pendingOrders', 'totalDue',
            'recentSales', 'topProducts',
            'monthlySales', 'monthlyExpenses', 'categoryRevenue'
        ));
    }
}
