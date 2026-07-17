<?php

namespace App\Http\Controllers;

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

        $transaction = Transaction::create([
            'date' => now(),
            'type' => Transaction::TYPE_EXPENSE,
            'amount' => $validated['amount'],
            'paid_amount' => $validated['amount'],
            'due_amount' => 0,
            'created_by' => auth('admin')->id(),
        ]);

        Expense::create([
            'table_name' => 'expenses',
            'fk_transaction_id' => $transaction->id,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Expense', Expense::latest()->first());

        return redirect()->route('expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }
}
