<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Models\Configuration;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::with(['product', 'warehouse']);

        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($productId = $request->input('fk_product_id')) {
            $query->where('fk_product_id', $productId);
        }

        $stocks = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($stocks);
        }

        $products = Product::where('status', 'active')->orderBy('name')->get();
        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('stocks.index', compact('stocks', 'products', 'currencySymbol'));
    }

    public function create()
    {
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('stocks.create', compact('products', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_product_id' => 'required|exists:products,id',
            'fk_warehouses_id' => 'nullable|exists:warehouses,id',
            'batch' => 'nullable|string|max:100',
            'lot' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:0',
            'buy_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,archive',
        ]);

        $adminId = auth('admin')->id();

        $stock = DB::transaction(function () use ($validated, $adminId) {
            $stock = Stock::create([
                'fk_product_id' => $validated['fk_product_id'],
                'fk_warehouses_id' => $validated['fk_warehouses_id'] ?? null,
                'batch' => $validated['batch'] ?? null,
                'lot' => $validated['lot'] ?? null,
                'quantity' => $validated['quantity'],
                'buy_price' => $validated['buy_price'],
                'sale_price' => $validated['sale_price'],
                'status' => $validated['status'],
                'accounting_version' => 1,
                'created_by' => $adminId,
            ]);

            app(AccountingPoster::class)->stockIn($stock, $adminId);

            return $stock;
        });

        ActivityLogger::created('Stock', $stock);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Stock created successfully.',
                'redirect' => route('stocks.index'),
            ]);
        }

        return redirect()->route('stocks.index')
            ->with('success', 'Stock created successfully.');
    }

    public function edit(Stock $stock)
    {
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('stocks.edit', compact('stock', 'products', 'warehouses'));
    }

    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'fk_product_id' => 'required|exists:products,id',
            'fk_warehouses_id' => 'nullable|exists:warehouses,id',
            'batch' => 'nullable|string|max:100',
            'lot' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:0',
            'buy_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,archive',
        ]);

        $old = $stock->toArray();
        $adminId = auth('admin')->id();
        $valueChanged = (int) $stock->quantity !== (int) $validated['quantity']
            || round((float) $stock->buy_price, 2) !== round((float) $validated['buy_price'], 2);

        DB::transaction(function () use ($stock, $validated, $adminId, $valueChanged) {
            $poster = app(AccountingPoster::class);

            if ($valueChanged || (! $stock->journal_entry_id && (int) $validated['quantity'] > 0 && (float) $validated['buy_price'] > 0)) {
                $poster->reverseOperational('stock', $stock->id, $adminId);

                $stock->update([
                    'fk_product_id' => $validated['fk_product_id'],
                    'fk_warehouses_id' => $validated['fk_warehouses_id'] ?? null,
                    'batch' => $validated['batch'] ?? null,
                    'lot' => $validated['lot'] ?? null,
                    'quantity' => $validated['quantity'],
                    'buy_price' => $validated['buy_price'],
                    'sale_price' => $validated['sale_price'],
                    'status' => $validated['status'],
                    'journal_entry_id' => null,
                    'accounting_version' => max(1, (int) $stock->accounting_version) + 1,
                    'updated_by' => $adminId,
                ]);

                $poster->stockIn($stock->fresh(), $adminId);

                return;
            }

            $stock->update([
                'fk_product_id' => $validated['fk_product_id'],
                'fk_warehouses_id' => $validated['fk_warehouses_id'] ?? null,
                'batch' => $validated['batch'] ?? null,
                'lot' => $validated['lot'] ?? null,
                'quantity' => $validated['quantity'],
                'buy_price' => $validated['buy_price'],
                'sale_price' => $validated['sale_price'],
                'status' => $validated['status'],
                'updated_by' => $adminId,
            ]);
        });

        ActivityLogger::updated('Stock', $stock, $stock->toArray());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Stock updated successfully.',
                'redirect' => route('stocks.index'),
            ]);
        }

        return redirect()->route('stocks.index')
            ->with('success', 'Stock updated successfully.');
    }

    public function destroy(Request $request, Stock $stock)
    {
        $adminId = auth('admin')->id();

        DB::transaction(function () use ($stock, $adminId) {
            app(AccountingPoster::class)->reverseOperational('stock', $stock->id, $adminId);

            $stock->update(['deleted_by' => $adminId, 'journal_entry_id' => null]);
            $stock->delete();
        });

        ActivityLogger::deleted('Stock', $stock);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Stock deleted successfully.',
                'redirect' => route('stocks.index'),
            ]);
        }

        return redirect()->route('stocks.index')
            ->with('success', 'Stock deleted successfully.');
    }
}
