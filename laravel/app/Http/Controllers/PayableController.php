<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Payable;
use Illuminate\Http\Request;

class PayableController extends Controller
{
    public function index(Request $request)
    {
        $query = Payable::query();

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        $payables = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $totalPayable = (clone $payables)->sum('amount');

        if ($request->expectsJson()) {
            return response()->json($payables);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('payables.index', compact('payables', 'totalPayable', 'currencySymbol'));
    }
}
