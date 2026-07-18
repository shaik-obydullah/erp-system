<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\PurchaseOrder;
use App\Models\ShipmentReturn;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ShipmentReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = ShipmentReturn::with('purchaseOrder');

        if ($search = $request->input('search')) {
            $query->where('remark', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $returns = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($returns);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('shipment-returns.index', compact('returns', 'currencySymbol'));
    }

    public function create()
    {
        $purchaseOrders = PurchaseOrder::orderBy('id', 'desc')->get();

        return view('shipment-returns.create', compact('purchaseOrders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_po_id' => 'required|exists:purchase_orders,id',
            'invoice_amount' => 'required|numeric|min:0',
            'return_reason' => 'required|in:damaged,incorrect_item,excess_quantity,other',
            'status' => 'required|in:pending,processed,completed,rejected',
            'remark' => 'nullable|string',
        ]);

        ShipmentReturn::create([
            'fk_po_id' => $validated['fk_po_id'],
            'invoice_amount' => $validated['invoice_amount'],
            'return_reason' => $validated['return_reason'],
            'status' => $validated['status'],
            'remark' => $validated['remark'] ?? null,
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Shipment Return', ShipmentReturn::latest('id')->first());

        return redirect()->route('shipment-returns.index')
            ->with('success', 'Shipment return created successfully.');
    }

    public function edit(ShipmentReturn $shipmentReturn)
    {
        $purchaseOrders = PurchaseOrder::orderBy('id', 'desc')->get();

        return view('shipment-returns.edit', compact('shipmentReturn', 'purchaseOrders'));
    }

    public function update(Request $request, ShipmentReturn $shipmentReturn)
    {
        $validated = $request->validate([
            'fk_po_id' => 'required|exists:purchase_orders,id',
            'invoice_amount' => 'required|numeric|min:0',
            'return_reason' => 'required|in:damaged,incorrect_item,excess_quantity,other',
            'status' => 'required|in:pending,processed,completed,rejected',
            'remark' => 'nullable|string',
        ]);

        $shipmentReturn->update([
            'fk_po_id' => $validated['fk_po_id'],
            'invoice_amount' => $validated['invoice_amount'],
            'return_reason' => $validated['return_reason'],
            'status' => $validated['status'],
            'remark' => $validated['remark'] ?? null,
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Shipment Return', $shipmentReturn, $shipmentReturn->toArray());

        return redirect()->route('shipment-returns.index')
            ->with('success', 'Shipment return updated successfully.');
    }

    public function destroy(Request $request, ShipmentReturn $shipmentReturn)
    {
        ActivityLogger::deleted('Shipment Return', $shipmentReturn);
        $shipmentReturn->update(['deleted_by' => auth('admin')->id()]);
        $shipmentReturn->delete();

        return redirect()->route('shipment-returns.index')
            ->with('success', 'Shipment return deleted successfully.');
    }
}
