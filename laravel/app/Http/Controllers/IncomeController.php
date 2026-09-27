<?php

namespace App\Http\Controllers;

use App\Models\Cashbook;
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

        $adminId = auth('admin')->id();

        $transaction = Transaction::create([
            'date' => now(),
            'type' => Transaction::TYPE_INCOME,
            'fk_reference_id' => null,
            'amount' => $validated['amount'],
            'paid_amount' => $validated['amount'],
            'due_amount' => 0,
            'created_by' => $adminId,
        ]);

        Income::create([
            'table_name' => 'incomes',
            'fk_transaction_id' => $transaction->id,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'created_by' => $adminId,
        ]);

        Cashbook::create([
            'table_name' => 'incomes',
            'fk_reference_id' => $transaction->id,
            'description' => $validated['description'],
            'in_amount' => $validated['amount'],
            'out_amount' => 0,
            'amount_payable' => 0,
            'amount_receivable' => 0,
            'created_by' => $adminId,
        ]);

        ActivityLogger::created('Income', Income::latest('id')->first());

        return redirect()->route('incomes.index')
            ->with('success', 'Income recorded successfully.');
    }
}
