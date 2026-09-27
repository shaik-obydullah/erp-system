<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $salesQuery = Sale::query()
            ->join('transactions', function ($join) {
                $join->on('sales.id', '=', 'transactions.fk_reference_id')
                    ->where('transactions.type', Transaction::TYPE_SALE_INCOME);
            });

        $totalSales = (clone $salesQuery)->sum('sales.grand_total');
        $income = (clone $salesQuery)->sum('sales.paid_amount');
        $expenses = Expense::query()
            ->join('transactions', 'expenses.fk_transaction_id', '=', 'transactions.id')
            ->where('transactions.type', Transaction::TYPE_EXPENSE)
            ->sum('expenses.amount');
        $netProfit = $income - $expenses;

        $totalProducts = Product::where('status', 'active')->count();
        $totalCustomers = Customer::count();
        $totalSuppliers = Supplier::where('status', 'active')->count();

        $chartLabels = [];
        $chartSales = [];
        $chartExpenses = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $chartLabels[] = $monthStart->format('M Y');

            $chartSales[] = (float) Sale::query()
                ->join('transactions', function ($join) {
                    $join->on('sales.id', '=', 'transactions.fk_reference_id')
                        ->where('transactions.type', Transaction::TYPE_SALE_INCOME);
                })
                ->whereBetween('transactions.date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('sales.grand_total');

            $chartExpenses[] = (float) Expense::query()
                ->join('transactions', 'expenses.fk_transaction_id', '=', 'transactions.id')
                ->where('transactions.type', Transaction::TYPE_EXPENSE)
                ->whereBetween('transactions.date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('expenses.amount');
        }

        $monthlySalesJson = json_encode($chartSales);
        $monthlyExpensesJson = json_encode($chartExpenses);
        $chartLabelsJson = json_encode($chartLabels);

        $top5Products = Product::select('products.*')
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

        $recentSales = Sale::with('transaction')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('reports.index', compact(
            'currencySymbol',
            'totalSales', 'income', 'expenses', 'netProfit',
            'totalProducts', 'totalCustomers', 'totalSuppliers',
            'top5Products', 'recentSales',
            'monthlySalesJson', 'monthlyExpensesJson', 'chartLabelsJson',
        ));
    }

    public function sales(Request $request)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');
        $now = Carbon::now();
        $startDate = $request->input('start_date', $now->copy()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', $now->copy()->endOfMonth()->toDateString());
        $type = $request->input('type');

        $query = Sale::query()
            ->select('sales.*')
            ->join('transactions', function ($join) {
                $join->on('sales.id', '=', 'transactions.fk_reference_id')
                    ->where('transactions.type', Transaction::TYPE_SALE_INCOME);
            })
            ->whereBetween('transactions.date', [$startDate, $endDate]);

        if ($type) {
            $query->where('sales.type', $type);
        }

        $summaryQuery = clone $query;

        $totalSalesCount = (clone $summaryQuery)->count();
        $totalRevenue = (float) (clone $summaryQuery)->sum('sales.grand_total');
        $totalPaid = (float) (clone $summaryQuery)->sum('sales.paid_amount');
        $totalDue = (float) (clone $summaryQuery)->sum('sales.sale_due');

        $chartLabels = [];
        $chartData = [];

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $current = $start->copy()->startOfMonth();
        while ($current->lte($end)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $chartLabels[] = $current->format('M Y');
            $chartData[] = (float) Sale::query()
                ->join('transactions', function ($join) {
                    $join->on('sales.id', '=', 'transactions.fk_reference_id')
                        ->where('transactions.type', Transaction::TYPE_SALE_INCOME);
                })
                ->whereBetween('transactions.date', [
                    $monthStart->toDateString(),
                    $monthEnd->toDateString(),
                ])
                ->when($type, fn ($q) => $q->where('sales.type', $type))
                ->sum('sales.grand_total');

            $current->addMonth();
        }

        $monthlySalesJson = json_encode($chartData);
        $chartLabelsJson = json_encode($chartLabels);

        $sales = $query->orderByDesc('transactions.date')
            ->orderByDesc('sales.id')
            ->paginate(20)
            ->withQueryString();

        return view('reports.sales', compact(
            'currencySymbol', 'startDate', 'endDate', 'type',
            'totalSalesCount', 'totalRevenue', 'totalPaid', 'totalDue',
            'sales', 'monthlySalesJson', 'chartLabelsJson',
        ));
    }

    public function income(Request $request)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');
        $now = Carbon::now();
        $startDate = $request->input('start_date', $now->copy()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', $now->copy()->endOfMonth()->toDateString());

        $query = Income::query()
            ->select('incomes.*')
            ->join('transactions', 'incomes.fk_transaction_id', '=', 'transactions.id')
            ->whereBetween('transactions.date', [$startDate, $endDate]);

        $totalIncome = (float) (clone $query)->sum('incomes.amount');

        $chartLabels = [];
        $chartData = [];

        $current = Carbon::parse($startDate)->startOfMonth();
        $end = Carbon::parse($endDate);

        while ($current->lte($end)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $chartLabels[] = $current->format('M Y');
            $chartData[] = (float) Income::query()
                ->join('transactions', 'incomes.fk_transaction_id', '=', 'transactions.id')
                ->whereBetween('transactions.date', [
                    $monthStart->toDateString(),
                    $monthEnd->toDateString(),
                ])
                ->sum('incomes.amount');

            $current->addMonth();
        }

        $monthlyIncomeJson = json_encode($chartData);
        $chartLabelsJson = json_encode($chartLabels);

        $incomes = $query->orderByDesc('transactions.date')
            ->orderByDesc('incomes.id')
            ->paginate(20)
            ->withQueryString();

        return view('reports.income', compact(
            'currencySymbol', 'startDate', 'endDate',
            'totalIncome', 'incomes',
            'monthlyIncomeJson', 'chartLabelsJson',
        ));
    }

    public function expense(Request $request)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');
        $now = Carbon::now();
        $startDate = $request->input('start_date', $now->copy()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', $now->copy()->endOfMonth()->toDateString());

        $query = Expense::query()
            ->select('expenses.*')
            ->join('transactions', 'expenses.fk_transaction_id', '=', 'transactions.id')
            ->whereBetween('transactions.date', [$startDate, $endDate]);

        $totalExpenses = (float) (clone $query)->sum('expenses.amount');

        $chartLabels = [];
        $chartData = [];

        $current = Carbon::parse($startDate)->startOfMonth();
        $end = Carbon::parse($endDate);

        while ($current->lte($end)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $chartLabels[] = $current->format('M Y');
            $chartData[] = (float) Expense::query()
                ->join('transactions', 'expenses.fk_transaction_id', '=', 'transactions.id')
                ->whereBetween('transactions.date', [
                    $monthStart->toDateString(),
                    $monthEnd->toDateString(),
                ])
                ->sum('expenses.amount');

            $current->addMonth();
        }

        $monthlyExpensesJson = json_encode($chartData);
        $chartLabelsJson = json_encode($chartLabels);

        $expenses = $query->orderByDesc('transactions.date')
            ->orderByDesc('expenses.id')
            ->paginate(20)
            ->withQueryString();

        return view('reports.expense', compact(
            'currencySymbol', 'startDate', 'endDate',
            'totalExpenses', 'expenses',
            'monthlyExpensesJson', 'chartLabelsJson',
        ));
    }

    public function stock(Request $request)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');

        $totalStockValue = (float) Stock::where('status', 'active')
            ->sum(DB::raw('quantity * buy_price'));

        $lowStockItems = Stock::with('product')
            ->where('status', 'active')
            ->where('quantity', '<', 10)
            ->orderBy('quantity')
            ->paginate(20)
            ->withQueryString();

        $stockByCategory = Category::select('categories.name')
            ->selectRaw('COALESCE(SUM(stocks.quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(stocks.quantity * stocks.buy_price), 0) as total_value')
            ->leftJoin('products', 'products.fk_category_id', '=', 'categories.id')
            ->leftJoin('stocks', function ($join) {
                $join->on('stocks.fk_product_id', '=', 'products.id')
                    ->where('stocks.status', '=', 'active');
            })
            ->groupBy('categories.name')
            ->havingRaw('SUM(stocks.quantity) > 0')
            ->orderByDesc('total_value')
            ->get();

        $categoryLabelsJson = json_encode($stockByCategory->pluck('name')->toArray());
        $categoryValuesJson = json_encode($stockByCategory->pluck('total_value')->toArray());

        $stockList = Stock::with('product')
            ->where('status', 'active')
            ->orderByDesc(DB::raw('quantity * buy_price'))
            ->paginate(20)
            ->withQueryString();

        return view('reports.stock', compact(
            'currencySymbol', 'totalStockValue',
            'lowStockItems', 'stockByCategory',
            'categoryLabelsJson', 'categoryValuesJson',
            'stockList',
        ));
    }

    public function customer(Request $request)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');

        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $inactiveCustomers = Customer::where('status', '!=', 'active')->count();

        $topCustomers = Customer::select('customers.id', 'customers.name', 'customers.email')
            ->selectRaw('COUNT(sales.id) as order_count')
            ->selectRaw('COALESCE(SUM(sales.grand_total), 0) as total_spent')
            ->leftJoin('sales', function ($join) {
                $join->on('customers.id', '=', 'sales.fk_user_id')
                    ->whereNull('sales.deleted_at');
            })
            ->groupBy('customers.id', 'customers.name', 'customers.email')
            ->orderByDesc('order_count')
            ->limit(10)
            ->get();

        $totalBalance = (float) Customer::sum('balance');
        $positiveBalance = (float) Customer::where('balance', '>', 0)->sum('balance');
        $negativeBalance = (float) Customer::where('balance', '<', 0)->sum('balance');

        $topCustomerLabelsJson = json_encode($topCustomers->pluck('name')->toArray());
        $topCustomerValuesJson = json_encode($topCustomers->pluck('total_spent')->toArray());

        $customers = Customer::orderByDesc('balance')
            ->paginate(20)
            ->withQueryString();

        return view('reports.customer', compact(
            'currencySymbol', 'totalCustomers', 'activeCustomers', 'inactiveCustomers',
            'topCustomers', 'totalBalance', 'positiveBalance', 'negativeBalance',
            'customers', 'topCustomerLabelsJson', 'topCustomerValuesJson',
        ));
    }

    public function supplier(Request $request)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');

        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::where('status', 'active')->count();
        $inactiveSuppliers = Supplier::where('status', '!=', 'active')->count();

        $totalBalance = (float) Supplier::sum('balance');
        $positiveBalance = (float) Supplier::where('balance', '>', 0)->sum('balance');
        $negativeBalance = (float) Supplier::where('balance', '<', 0)->sum('balance');

        $suppliers = Supplier::orderByDesc('balance')
            ->paginate(20)
            ->withQueryString();

        return view('reports.supplier', compact(
            'currencySymbol', 'totalSuppliers', 'activeSuppliers', 'inactiveSuppliers',
            'totalBalance', 'positiveBalance', 'negativeBalance',
            'suppliers',
        ));
    }
}
