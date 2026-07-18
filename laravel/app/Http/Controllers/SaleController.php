<?php

namespace App\Http\Controllers;

use App\Models\Cashbook;
use App\Models\Configuration;
use App\Models\Sale;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with('details');

        if ($search = $request->input('search')) {
            $query->where('invoice_id', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $sales = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($sales);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('sales.index', compact('sales', 'currencySymbol'));
    }

    public function show(Sale $sale)
    {
        $sale->load('details', 'transaction');
        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('sales.show', compact('sale', 'currencySymbol'));
    }

    public function invoice(Sale $sale)
    {
        $sale->load('details', 'transaction');
        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('sales.invoice', compact('sale', 'currencySymbol'));
    }

    public function receiveDue(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $adminId = auth('admin')->id();
        $amount = min((float) $validated['amount'], $sale->sale_due);

        if ($amount <= 0) {
            return back()->with('error', 'No due amount to receive.');
        }

        $newPaidAmount = $sale->paid_amount + $amount;
        $newDue = max(0, $sale->sale_due - $amount);

        $sale->update([
            'paid_amount' => $newPaidAmount,
            'sale_due' => $newDue,
            'updated_by' => $adminId,
        ]);

        // Accounting: record payment transaction
        Transaction::create([
            'date' => now()->toDateString(),
            'type' => Transaction::TYPE_SALE_DUE,
            'fk_reference_id' => $sale->id,
            'amount' => $amount,
            'paid_amount' => $amount,
            'due_amount' => $newDue,
            'created_by' => $adminId,
        ]);

        // Cashbook: record payment received
        Cashbook::create([
            'table_name' => 'sales',
            'fk_reference_id' => $sale->id,
            'description' => "Due payment for {$sale->invoice_id}",
            'in_amount' => $amount,
            'out_amount' => 0,
            'amount_payable' => 0,
            'amount_receivable' => -$amount,
            'created_by' => $adminId,
        ]);

        ActivityLogger::updated('Sale', $sale, $sale->toArray());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Received {$amount}. Remaining due: {$newDue}",
                'redirect' => route('sales.show', $sale),
            ]);
        }

        return redirect()->route('sales.show', $sale)
            ->with('success', "Received {$amount}. Remaining due: {$newDue}");
    }
}
