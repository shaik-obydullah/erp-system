<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Models\Cashbook;
use App\Models\Configuration;
use App\Models\Income;
use App\Models\SaleReturn;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use App\Services\VatCalculator;
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

        $returnsBySale = SaleReturn::query()
            ->whereIn('status', ['approved', 'completed'])
            ->selectRaw('fk_sale_id, SUM(refund_amount) as refunded')
            ->groupBy('fk_sale_id')
            ->pluck('refunded', 'fk_sale_id');

        return view('incomes.index', compact('incomes', 'currencySymbol', 'returnsBySale'));
    }

    public function create()
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('incomes.create', compact('currencySymbol'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'vat_code' => 'nullable|in:standard,reduced,zero,exempt,outside_scope',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $adminId = auth('admin')->id();
        $calculated = app(VatCalculator::class)->extract(
            (float) $validated['amount'],
            $validated['vat_code'] ?? null,
            isset($validated['vat_rate']) ? (float) $validated['vat_rate'] : null
        );

        $transaction = Transaction::create([
            'date' => now(),
            'type' => Transaction::TYPE_INCOME,
            'fk_reference_id' => null,
            'amount' => $validated['amount'],
            'paid_amount' => $validated['amount'],
            'due_amount' => 0,
            'created_by' => $adminId,
        ]);

        $income = Income::create([
            'table_name' => 'incomes',
            'fk_transaction_id' => $transaction->id,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'vat_code' => $calculated['code'],
            'vat_rate' => $calculated['rate'],
            'net_amount' => $calculated['net'],
            'vat_amount' => $calculated['vat'],
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

        app(AccountingPoster::class)->income($income, $adminId);

        ActivityLogger::created('Income', Income::latest('id')->first());

        return redirect()->route('incomes.index')
            ->with('success', 'Income recorded successfully.');
    }
}
