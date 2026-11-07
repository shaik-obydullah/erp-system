<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\PurchaseOrder;
use App\Models\Shipment;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use App\Services\ShipmentCostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    public function __construct(private readonly ShipmentCostService $shipmentCosts) {}

    public function index(Request $request)
    {
        $query = Shipment::with(['purchaseOrder', 'warehouse', 'costs']);

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

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('shipments.index', compact('shipments', 'currencySymbol'));
    }

    public function create()
    {
        $purchaseOrders = PurchaseOrder::orderBy('id', 'desc')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        return view('shipments.create', compact('purchaseOrders', 'warehouses', 'suppliers'));
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

        $costData = null;

        if ((float) $request->input('cost.amount', 0) > 0) {
            $costData = $request->validate([
                'cost.cost_type' => 'required|in:freight,duty,handling,insurance,other',
                'cost.amount' => 'required|numeric|min:0.01|max:999999999.99',
                'cost.fk_supplier_id' => 'nullable|exists:suppliers,id',
                'cost.payment_status' => 'required|in:paid,unpaid',
                'cost.cost_date' => 'required|date',
                'cost.invoice_number' => 'nullable|string|max:100',
                'cost.payment_method' => 'nullable|string|max:50',
                'cost.description' => 'nullable|string|max:1000',
            ])['cost'];
        }

        $adminId = auth('admin')->id();
        $costService = $this->shipmentCosts;

        $shipment = DB::transaction(function () use ($validated, $costData, $adminId, $costService) {
            $shipment = Shipment::create([
                'fk_po_id' => $validated['fk_po_id'],
                'fk_warehouse_id' => $validated['fk_warehouse_id'],
                'tracking_number' => $validated['tracking_number'],
                'received_date' => $validated['received_date'] ?? null,
                'status' => $validated['status'],
                'remark' => $validated['remark'] ?? null,
                'created_by' => $adminId,
            ]);

            if ($costData) {
                $costService->create($shipment, $costData, $adminId);
            }

            ActivityLogger::created('Shipment', $shipment);

            return $shipment;
        });

        return redirect()->route('shipments.index')
            ->with('success', 'Shipment created successfully.');
    }

    public function show(Shipment $shipment)
    {
        $shipment->load(['purchaseOrder.supplier', 'warehouse', 'costs.supplier']);
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('shipments.show', compact('shipment', 'suppliers', 'currencySymbol'));
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
            'tracking_number' => 'required|string|max:100|unique:shipments,tracking_number,'.$shipment->id,
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
}
