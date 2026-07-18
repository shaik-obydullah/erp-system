<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Receivable;
use Illuminate\Http\Request;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $query = Receivable::query();

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        $receivables = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $totalReceivable = (clone $receivables)->sum('amount');

        if ($request->expectsJson()) {
            return response()->json($receivables);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('receivables.index', compact('receivables', 'totalReceivable', 'currencySymbol'));
    }
}
