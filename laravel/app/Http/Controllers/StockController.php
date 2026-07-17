<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

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

        Stock::create([
            'fk_product_id' => $validated['fk_product_id'],
            'fk_warehouses_id' => $validated['fk_warehouses_id'] ?? null,
            'batch' => $validated['batch'] ?? null,
            'lot' => $validated['lot'] ?? null,
            'quantity' => $validated['quantity'],
            'buy_price' => $validated['buy_price'],
            'sale_price' => $validated['sale_price'],
            'status' => $validated['status'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Stock', Stock::latest()->first());

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

        $stock->update([
            'fk_product_id' => $validated['fk_product_id'],
            'fk_warehouses_id' => $validated['fk_warehouses_id'] ?? null,
            'batch' => $validated['batch'] ?? null,
            'lot' => $validated['lot'] ?? null,
            'quantity' => $validated['quantity'],
            'buy_price' => $validated['buy_price'],
            'sale_price' => $validated['sale_price'],
            'status' => $validated['status'],
            'updated_by' => auth('admin')->id(),
        ]);

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
        ActivityLogger::deleted('Stock', $stock);

        $stock->update(['deleted_by' => auth('admin')->id()]);
        $stock->delete();

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
