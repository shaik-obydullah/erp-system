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

        $totals = Cashbook::query()->selectRaw('
            COALESCE(SUM(in_amount), 0) as total_in,
            COALESCE(SUM(out_amount), 0) as total_out,
            COALESCE(SUM(amount_payable), 0) as total_payable,
            COALESCE(SUM(amount_receivable), 0) as total_receivable
        ')->first();

        $totalIn = (float) $totals->total_in;
        $totalOut = (float) $totals->total_out;
        $totalPayable = (float) $totals->total_payable;
        $totalReceivable = (float) $totals->total_receivable;
        $netCash = $totalIn - $totalOut;

        if ($request->expectsJson()) {
            return response()->json($entries);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('cashbook.index', compact(
            'entries',
            'totalIn',
            'totalOut',
            'totalPayable',
            'totalReceivable',
            'netCash',
            'currencySymbol'
        ));
    }
}
