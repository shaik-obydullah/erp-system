<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Income;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $query = Income::with('transaction');

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        $incomes = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($incomes);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('incomes.index', compact('incomes', 'currencySymbol'));
    }

    public function create()
    {
        return view('incomes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $transaction = Transaction::create([
            'date' => now(),
            'type' => Transaction::TYPE_INCOME,
            'amount' => $validated['amount'],
            'paid_amount' => $validated['amount'],
            'due_amount' => 0,
            'created_by' => auth('admin')->id(),
        ]);

        Income::create([
            'table_name' => 'incomes',
            'fk_transaction_id' => $transaction->id,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Income', Income::latest()->first());

        return redirect()->route('incomes.index')
            ->with('success', 'Income recorded successfully.');
    }
}
