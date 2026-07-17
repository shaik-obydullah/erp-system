<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Shipment::with(['purchaseOrder', 'warehouse']);

        if ($search = $request->input('search')) {
            $query->where('tracking_number', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $shipments = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($shipments);
        }

        return view('shipments.index', compact('shipments'));
    }

    public function create()
    {
        $purchaseOrders = PurchaseOrder::orderBy('id', 'desc')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('shipments.create', compact('purchaseOrders', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_po_id' => 'required|exists:purchase_orders,id',
            'fk_warehouse_id' => 'required|exists:warehouses,id',
            'tracking_number' => 'required|string|max:100|unique:shipments,tracking_number',
            'received_date' => 'nullable|date',
            'status' => 'required|in:pending,shipped,in_transit,delivered,canceled',
            'remark' => 'nullable|string',
        ]);

        Shipment::create([
            'fk_po_id' => $validated['fk_po_id'],
            'fk_warehouse_id' => $validated['fk_warehouse_id'],
            'tracking_number' => $validated['tracking_number'],
            'received_date' => $validated['received_date'] ?? null,
            'status' => $validated['status'],
            'remark' => $validated['remark'] ?? null,
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Shipment', Shipment::latest()->first());

        return redirect()->route('shipments.index')
            ->with('success', 'Shipment created successfully.');
    }

    public function show(Shipment $shipment)
    {
        $shipment->load(['purchaseOrder.supplier', 'warehouse']);
        return view('shipments.show', compact('shipment'));
    }

    public function edit(Shipment $shipment)
    {
        $purchaseOrders = PurchaseOrder::orderBy('id', 'desc')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('shipments.edit', compact('shipment', 'purchaseOrders', 'warehouses'));
    }

    public function update(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'fk_po_id' => 'required|exists:purchase_orders,id',
            'fk_warehouse_id' => 'required|exists:warehouses,id',
            'tracking_number' => 'required|string|max:100|unique:shipments,tracking_number,' . $shipment->id,
            'received_date' => 'nullable|date',
            'status' => 'required|in:pending,shipped,in_transit,delivered,canceled',
            'remark' => 'nullable|string',
        ]);

        $shipment->update([
            'fk_po_id' => $validated['fk_po_id'],
            'fk_warehouse_id' => $validated['fk_warehouse_id'],
            'tracking_number' => $validated['tracking_number'],
            'received_date' => $validated['received_date'] ?? null,
            'status' => $validated['status'],
            'remark' => $validated['remark'] ?? null,
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Shipment', $shipment, $shipment->toArray());

        return redirect()->route('shipments.index')
            ->with('success', 'Shipment updated successfully.');
    }

    public function destroy(Request $request, Shipment $shipment)
    {
        ActivityLogger::deleted('Shipment', $shipment);
        $shipment->update(['deleted_by' => auth('admin')->id()]);
        $shipment->delete();

        return redirect()->route('shipments.index')
            ->with('success', 'Shipment deleted successfully.');
    }
}
