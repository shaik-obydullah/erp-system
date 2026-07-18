<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $query = StockAdjustment::with(['stock.product', 'warehouse']);

        if ($search = $request->input('search')) {
            $query->whereHas('stock.product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($reason = $request->input('reason')) {
            $query->where('reason', $reason);
        }

        $adjustments = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($adjustments);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('stock-adjustments.index', compact('adjustments', 'currencySymbol'));
    }

    public function create()
    {
        $stocks = Stock::where('status', 'active')->with('product')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('stock-adjustments.create', compact('stocks', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_stock_id' => 'required|exists:stocks,id',
            'fk_warehouse_id' => 'nullable|exists:warehouses,id',
            'batch' => 'nullable|string|max:100',
            'lot' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|in:correction,damage,return',
        ]);

        $stock = Stock::findOrFail($validated['fk_stock_id']);

        if ($validated['reason'] === 'damage' || $validated['reason'] === 'return') {
            $newQty = $stock->quantity + $validated['quantity'];
        } else {
            $newQty = $stock->quantity - $validated['quantity'];
        }

        $stock->update([
            'quantity' => max(0, $newQty),
            'updated_by' => auth('admin')->id(),
        ]);

        StockAdjustment::create([
            'fk_stock_id' => $validated['fk_stock_id'],
            'fk_warehouse_id' => $validated['fk_warehouse_id'] ?? null,
            'batch' => $validated['batch'] ?? null,
            'lot' => $validated['lot'] ?? null,
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Stock Adjustment', StockAdjustment::latest('id')->first());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Stock adjustment recorded successfully.',
                'redirect' => route('stock-adjustments.index'),
            ]);
        }

        return redirect()->route('stock-adjustments.index')
            ->with('success', 'Stock adjustment recorded successfully.');
    }
}
