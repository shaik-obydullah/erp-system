<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Models\Configuration;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $stockOptions = $stocks->map(fn ($stock) => [
            'id' => $stock->id,
            'name' => $stock->product->name ?? 'N/A',
            'sku' => $stock->product->sku ?? null,
            'quantity' => $stock->quantity,
            'buy_price' => (float) $stock->buy_price,
            'batch' => $stock->batch,
            'lot' => $stock->lot,
        ])->values();

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('stock-adjustments.create', compact('warehouses', 'stockOptions', 'currencySymbol'));
    }

    public function store(Request $request, AccountingPoster $poster)
    {
        $validated = $request->validate([
            'fk_stock_id' => 'required|exists:stocks,id',
            'fk_warehouse_id' => 'nullable|exists:warehouses,id',
            'batch' => 'nullable|string|max:100',
            'lot' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|in:correction,damage,return',
        ]);

        $adminId = auth('admin')->id();

        $adjustment = DB::transaction(function () use ($validated, $adminId, $poster) {
            $stock = Stock::findOrFail($validated['fk_stock_id']);

            // A return puts stock back on the shelf. Damage and corrections
            // take stock out, so the quantity must go down for those reasons.
            $newQty = $validated['reason'] === 'return'
                ? $stock->quantity + $validated['quantity']
                : $stock->quantity - $validated['quantity'];

            $stock->update([
                'quantity' => max(0, $newQty),
                'updated_by' => $adminId,
            ]);

            $adjustment = StockAdjustment::create([
                'fk_stock_id' => $validated['fk_stock_id'],
                'fk_warehouse_id' => $validated['fk_warehouse_id'] ?? null,
                'batch' => $validated['batch'] ?? null,
                'lot' => $validated['lot'] ?? null,
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'],
                'created_by' => $adminId,
            ]);

            // Write the loss (or the restock) into the double-entry ledger.
            $poster->stockAdjustment($adjustment, $adminId);

            return $adjustment;
        });

        ActivityLogger::created('Stock Adjustment', $adjustment);

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
