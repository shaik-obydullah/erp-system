<?php

namespace App\Http\Controllers;

use App\Models\Cashbook;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerFundController extends Controller
{
    public function index(Request $request)
    {
        $query = Cashbook::where('table_name', 'customers')
            ->with('reference');

        if ($customerId = $request->input('customer_id')) {
            $query->where('fk_reference_id', $customerId);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $funds = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('customers.fund', compact('funds', 'customers', 'currencySymbol'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01|max:99999999.99',
            'description' => 'nullable|string|max:500',
        ]);

        $adminId = auth('admin')->id();
        $amount = (float) $validated['amount'];

        DB::beginTransaction();

        try {
            $customer = Customer::findOrFail($validated['customer_id']);

            // Update customer balance
            $customer->increment('balance', $amount);
            $customer->update(['updated_by' => $adminId]);

            // Record transaction (accounting ledger)
            $transaction = Transaction::create([
                'date' => now()->toDateString(),
                'type' => Transaction::TYPE_USER_FUND,
                'fk_reference_id' => $customer->id,
                'amount' => $amount,
                'paid_amount' => $amount,
                'due_amount' => 0,
                'created_by' => $adminId,
            ]);

            // Record cashbook entry
            $cashbookEntry = Cashbook::create([
                'table_name' => 'customers',
                'fk_reference_id' => $customer->id,
                'description' => $validated['description'] ?? "Fund added to {$customer->name}",
                'in_amount' => $amount,
                'out_amount' => 0,
                'amount_payable' => 0,
                'amount_receivable' => $amount,
                'created_by' => $adminId,
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Rs. {$amount} added to {$customer->name}'s balance.",
                    'redirect' => route('customers.fund.index'),
                ]);
            }

            return redirect()->route('customers.fund.index')
                ->with('success', "Rs. {$amount} added to {$customer->name}'s balance.");

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to add fund: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to add fund: ' . $e->getMessage());
        }
    }

    public function balance(Request $request)
    {
        $customerId = $request->input('customer_id');

        if (!$customerId) {
            return response()->json(['balance' => 0]);
        }

        $customer = Customer::find($customerId);

        return response()->json([
            'balance' => $customer ? $customer->balance : 0,
        ]);
    }
}
