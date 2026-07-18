<?php

namespace App\Http\Controllers;

use App\Models\Need;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['need', 'supplier']);

        if ($search = $request->input('search')) {
            $query->where('order_number', 'like', "%{$search}%");
        }

        $purchaseOrders = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $currencySymbol = \App\Models\Configuration::get('currency_symbol', '$');

        if ($request->expectsJson()) {
            return response()->json($purchaseOrders);
        }

        return view('purchase-orders.index', compact('purchaseOrders', 'currencySymbol'));
    }

    public function create()
    {
        $needs = Need::orderBy('id', 'desc')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        return view('purchase-orders.create', compact('needs', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_need_id' => 'required|exists:needs,id',
            'fk_supplier_id' => 'required|exists:suppliers,id',
            'order_number' => 'required|string|max:50|unique:purchase_orders,order_number',
            'total_amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
            'due_amount' => 'required|numeric|min:0',
        ]);

        PurchaseOrder::create([
            'fk_need_id' => $validated['fk_need_id'],
            'fk_supplier_id' => $validated['fk_supplier_id'],
            'order_number' => $validated['order_number'],
            'total_amount' => $validated['total_amount'],
            'remarks' => $validated['remarks'] ?? null,
            'due_amount' => $validated['due_amount'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Purchase Order', PurchaseOrder::latest('id')->first());

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order created successfully.');
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $needs = Need::orderBy('id', 'desc')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        return view('purchase-orders.edit', compact('purchaseOrder', 'needs', 'suppliers'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'fk_need_id' => 'required|exists:needs,id',
            'fk_supplier_id' => 'required|exists:suppliers,id',
            'order_number' => 'required|string|max:50|unique:purchase_orders,order_number,' . $purchaseOrder->id,
            'total_amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
            'due_amount' => 'required|numeric|min:0',
        ]);

        $purchaseOrder->update([
            'fk_need_id' => $validated['fk_need_id'],
            'fk_supplier_id' => $validated['fk_supplier_id'],
            'order_number' => $validated['order_number'],
            'total_amount' => $validated['total_amount'],
            'remarks' => $validated['remarks'] ?? null,
            'due_amount' => $validated['due_amount'],
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Purchase Order', $purchaseOrder, $purchaseOrder->toArray());

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order updated successfully.');
    }

    public function destroy(Request $request, PurchaseOrder $purchaseOrder)
    {
        ActivityLogger::deleted('Purchase Order', $purchaseOrder);
        $purchaseOrder->update(['deleted_by' => auth('admin')->id()]);
        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order deleted successfully.');
    }
}
