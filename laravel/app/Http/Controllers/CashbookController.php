<?php

namespace App\Http\Controllers;

use App\Models\Cashbook;
use App\Models\Configuration;
use Illuminate\Http\Request;

class CashbookController extends Controller
{
    public function index(Request $request)
    {
        $query = Cashbook::query();

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        $entries = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $allEntries = Cashbook::query()->get();
        $totalIn = $allEntries->sum('in_amount');
        $totalOut = $allEntries->sum('out_amount');
        $totalPayable = $allEntries->sum('amount_payable');
        $totalReceivable = $allEntries->sum('amount_receivable');

        if ($request->expectsJson()) {
            return response()->json($entries);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('cashbook.index', compact('entries', 'totalIn', 'totalOut', 'totalPayable', 'totalReceivable', 'currencySymbol'));
    }
}
