<?php

namespace App\Http\Controllers;

use App\Models\Cashbook;
use App\Models\Configuration;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierFundController extends Controller
{
    public function index(Request $request)
    {
        $query = Cashbook::where('table_name', 'suppliers')
            ->with('reference');

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('fk_reference_id', $supplierId);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $funds = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('suppliers.fund', compact('funds', 'suppliers', 'currencySymbol'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01|max:99999999.99',
            'description' => 'nullable|string|max:500',
        ]);

        $adminId = auth('admin')->id();
        $amount = (float) $validated['amount'];

        DB::beginTransaction();

        try {
            $supplier = Supplier::findOrFail($validated['supplier_id']);

            $supplier->increment('balance', $amount);
            $supplier->update(['updated_by' => $adminId]);

            $transaction = Transaction::create([
                'date' => now()->toDateString(),
                'type' => 'supplierDeposit',
                'fk_reference_id' => $supplier->id,
                'amount' => $amount,
                'paid_amount' => $amount,
                'due_amount' => 0,
                'created_by' => $adminId,
            ]);

            Cashbook::create([
                'table_name' => 'suppliers',
                'fk_reference_id' => $supplier->id,
                'description' => $validated['description'] ?? "Fund added to {$supplier->name}",
                'in_amount' => $amount,
                'out_amount' => 0,
                'amount_payable' => 0,
                'amount_receivable' => $amount,
                'created_by' => $adminId,
            ]);

            DB::commit();

            return redirect()->route('suppliers.fund.index')
                ->with('success', "{$amount} added to {$supplier->name}'s balance.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to add fund: ' . $e->getMessage());
        }
    }

    public function balance(Request $request)
    {
        $supplierId = $request->input('supplier_id');

        if (!$supplierId) {
            return response()->json(['balance' => 0]);
        }

        $supplier = Supplier::find($supplierId);

        return response()->json([
            'balance' => $supplier ? $supplier->balance : 0,
        ]);
    }
}
