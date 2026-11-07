<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Accounting\LedgerService;
use App\Models\JournalEntry;
use App\Models\Need;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use App\Services\VatCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $currencySymbol = \App\Models\Configuration::get('currency_symbol', '$');

        return view('purchase-orders.create', compact('needs', 'suppliers', 'currencySymbol'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_need_id' => 'required|exists:needs,id',
            'fk_supplier_id' => 'required|exists:suppliers,id',
            'order_number' => 'required|string|max:50|unique:purchase_orders,order_number',
            'total_amount' => 'required|numeric|min:0',
            'vat_code' => 'nullable|in:standard,reduced,zero,exempt,outside_scope',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'remarks' => 'nullable|string',
            'due_amount' => 'required|numeric|min:0',
        ]);

        $calculated = app(VatCalculator::class)->extract(
            (float) $validated['total_amount'],
            $validated['vat_code'] ?? null,
            isset($validated['vat_rate']) ? (float) $validated['vat_rate'] : null
        );

        $purchaseOrder = PurchaseOrder::create([
            'fk_need_id' => $validated['fk_need_id'],
            'fk_supplier_id' => $validated['fk_supplier_id'],
            'order_number' => $validated['order_number'],
            'total_amount' => $validated['total_amount'],
            'vat_code' => $calculated['code'],
            'vat_rate' => $calculated['rate'],
            'net_amount' => $calculated['net'],
            'vat_amount' => $calculated['vat'],
            'remarks' => $validated['remarks'] ?? null,
            'due_amount' => $validated['due_amount'],
            'created_by' => auth('admin')->id(),
        ]);

        app(AccountingPoster::class)->purchaseOrder($purchaseOrder, auth('admin')->id());

        ActivityLogger::created('Purchase Order', $purchaseOrder);

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order created successfully.');
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $needs = Need::orderBy('id', 'desc')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $currencySymbol = \App\Models\Configuration::get('currency_symbol', '$');

        return view('purchase-orders.edit', compact('purchaseOrder', 'needs', 'suppliers', 'currencySymbol'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'fk_need_id' => 'required|exists:needs,id',
            'fk_supplier_id' => 'required|exists:suppliers,id',
            'order_number' => 'required|string|max:50|unique:purchase_orders,order_number,' . $purchaseOrder->id,
            'total_amount' => 'required|numeric|min:0',
            'vat_code' => 'nullable|in:standard,reduced,zero,exempt,outside_scope',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'remarks' => 'nullable|string',
            'due_amount' => 'required|numeric|min:0',
        ]);

        $calculated = app(VatCalculator::class)->extract(
            (float) $validated['total_amount'],
            $validated['vat_code'] ?? null,
            isset($validated['vat_rate']) ? (float) $validated['vat_rate'] : null
        );

        $suffix = null;

        DB::transaction(function () use ($purchaseOrder, $validated, $calculated, &$suffix) {
            if ($purchaseOrder->journal_entry_id) {
                $entry = JournalEntry::find($purchaseOrder->journal_entry_id);

                if ($entry) {
                    app(LedgerService::class)->reverse($entry, "Purchase order {$purchaseOrder->order_number} updated");
                    $suffix = ':v' . $entry->id;
                }
            }

            $purchaseOrder->update([
                'fk_need_id' => $validated['fk_need_id'],
                'fk_supplier_id' => $validated['fk_supplier_id'],
                'order_number' => $validated['order_number'],
                'total_amount' => $validated['total_amount'],
                'vat_code' => $calculated['code'],
                'vat_rate' => $calculated['rate'],
                'net_amount' => $calculated['net'],
                'vat_amount' => $calculated['vat'],
                'remarks' => $validated['remarks'] ?? null,
                'due_amount' => $validated['due_amount'],
                'journal_entry_id' => null,
                'updated_by' => auth('admin')->id(),
            ]);

            app(AccountingPoster::class)->purchaseOrder($purchaseOrder, auth('admin')->id(), $suffix);
        });

        ActivityLogger::updated('Purchase Order', $purchaseOrder, $purchaseOrder->toArray());

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order updated successfully.');
    }

    public function destroy(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->journal_entry_id) {
            $entry = JournalEntry::find($purchaseOrder->journal_entry_id);

            if ($entry) {
                app(LedgerService::class)->reverse($entry, "Purchase order {$purchaseOrder->order_number} deleted");
            }
        }

        ActivityLogger::deleted('Purchase Order', $purchaseOrder);
        $purchaseOrder->update(['deleted_by' => auth('admin')->id()]);
        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order deleted successfully.');
    }
}
