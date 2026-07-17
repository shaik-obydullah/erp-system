<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::query();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $transactions = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($transactions);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('transactions.index', compact('transactions', 'currencySymbol'));
    }
}
