<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\ShipmentCost;
use App\Services\ActivityLogger;
use App\Services\ShipmentCostService;
use Illuminate\Http\Request;

class ShipmentCostController extends Controller
{
    public function __construct(private readonly ShipmentCostService $costs)
    {
    }

    public function store(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'cost_type' => 'required|in:freight,duty,handling,insurance,other',
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'fk_supplier_id' => 'nullable|exists:suppliers,id',
            'payment_status' => 'required|in:paid,unpaid',
            'cost_date' => 'required|date',
            'invoice_number' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        $cost = $this->costs->create($shipment, $this->costPayload($validated), auth('admin')->id());

        ActivityLogger::created('ShipmentCost', $cost);

        return redirect()->route('shipments.show', $shipment)
            ->with('success', 'Shipment cost recorded and posted to accounting.');
    }

    public function pay(Request $request, ShipmentCost $shipmentCost)
    {
        $validated = $request->validate([
            'paid_date' => 'nullable|date',
        ]);

        $this->costs->markPaid($shipmentCost, $validated, auth('admin')->id());

        return redirect()->route('shipments.show', $shipmentCost->fk_shipment_id)
            ->with('success', 'Shipment cost marked as paid and posted to accounting.');
    }

    public function destroy(ShipmentCost $shipmentCost)
    {
        $shipmentId = $shipmentCost->fk_shipment_id;

        $this->costs->delete($shipmentCost, auth('admin')->id());

        ActivityLogger::deleted('ShipmentCost', $shipmentCost);

        return redirect()->route('shipments.show', $shipmentId)
            ->with('success', 'Shipment cost removed and its accounting entry reversed.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function costPayload(array $validated): array
    {
        return [
            'cost_type' => $validated['cost_type'],
            'amount' => $validated['amount'],
            'fk_supplier_id' => $validated['fk_supplier_id'] ?? null,
            'payment_status' => $validated['payment_status'],
            'cost_date' => $validated['cost_date'],
            'invoice_number' => $validated['invoice_number'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'description' => $validated['description'] ?? null,
        ];
    }
}
