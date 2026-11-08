<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Models\Configuration;
use App\Models\PurchaseOrder;
use App\Models\ShipmentReturn;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if ($reason = $request->input('return_reason')) {
            $query->where('return_reason', $reason);
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

        $adminId = auth('admin')->id();

        $shipmentReturn = DB::transaction(function () use ($validated, $adminId) {
            $shipmentReturn = ShipmentReturn::create([
                'fk_po_id' => $validated['fk_po_id'],
                'invoice_amount' => $validated['invoice_amount'],
                'return_reason' => $validated['return_reason'],
                'status' => $validated['status'],
                'remark' => $validated['remark'] ?? null,
                'accounting_version' => 1,
                'created_by' => $adminId,
            ]);

            app(AccountingPoster::class)->shipmentReturn($shipmentReturn, $adminId);

            return $shipmentReturn;
        });

        ActivityLogger::created('Shipment Return', $shipmentReturn);

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

        $adminId = auth('admin')->id();
        $postable = ['processed', 'completed'];
        $wasPosted = in_array($shipmentReturn->status, $postable, true);
        $willPost = in_array($validated['status'], $postable, true);
        $financialChanged = $wasPosted !== $willPost
            || ($willPost && (
                round((float) $shipmentReturn->invoice_amount, 2) !== round((float) $validated['invoice_amount'], 2)
                || (int) $shipmentReturn->fk_po_id !== (int) $validated['fk_po_id']
            ));

        DB::transaction(function () use ($shipmentReturn, $validated, $adminId, $financialChanged) {
            $poster = app(AccountingPoster::class);

            if ($financialChanged) {
                $poster->reverseOperational('shipment_return', $shipmentReturn->id, $adminId);

                $shipmentReturn->update([
                    'fk_po_id' => $validated['fk_po_id'],
                    'invoice_amount' => $validated['invoice_amount'],
                    'return_reason' => $validated['return_reason'],
                    'status' => $validated['status'],
                    'remark' => $validated['remark'] ?? null,
                    'journal_entry_id' => null,
                    'accounting_version' => max(1, (int) $shipmentReturn->accounting_version) + 1,
                    'updated_by' => $adminId,
                ]);

                $poster->shipmentReturn($shipmentReturn->fresh(), $adminId);

                return;
            }

            $shipmentReturn->update([
                'fk_po_id' => $validated['fk_po_id'],
                'invoice_amount' => $validated['invoice_amount'],
                'return_reason' => $validated['return_reason'],
                'status' => $validated['status'],
                'remark' => $validated['remark'] ?? null,
                'updated_by' => $adminId,
            ]);
        });

        ActivityLogger::updated('Shipment Return', $shipmentReturn, $shipmentReturn->toArray());

        return redirect()->route('shipment-returns.index')
            ->with('success', 'Shipment return updated successfully.');
    }
}
