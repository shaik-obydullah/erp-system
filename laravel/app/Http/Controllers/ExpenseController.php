<?php

namespace App\Http\Controllers;

use App\Models\Cashbook;
use App\Models\Configuration;
use App\Models\Expense;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('transaction');

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        $expenses = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($expenses);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('expenses.index', compact('expenses', 'currencySymbol'));
    }

    public function create()
    {
        return view('expenses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $adminId = auth('admin')->id();

        $transaction = Transaction::create([
            'date' => now(),
            'type' => Transaction::TYPE_EXPENSE,
            'fk_reference_id' => null,
            'amount' => $validated['amount'],
            'paid_amount' => $validated['amount'],
            'due_amount' => 0,
            'created_by' => $adminId,
        ]);

        Expense::create([
            'table_name' => 'expenses',
            'fk_transaction_id' => $transaction->id,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'created_by' => $adminId,
        ]);

        Cashbook::create([
            'table_name' => 'expenses',
            'fk_reference_id' => $transaction->id,
            'description' => $validated['description'],
            'in_amount' => 0,
            'out_amount' => $validated['amount'],
            'amount_payable' => 0,
            'amount_receivable' => 0,
            'created_by' => $adminId,
        ]);

        ActivityLogger::created('Expense', Expense::latest('id')->first());

        return redirect()->route('expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }
}
