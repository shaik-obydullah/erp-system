<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Sale;
use Illuminate\Http\Request;

class CustomerDueController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::where('sale_due', '>', 0)
            ->where('status', 'completed');

        if ($search = $request->input('search')) {
            $query->where('invoice_id', 'like', "%{$search}%");
        }

        $sales = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $totalDue = Sale::where('sale_due', '>', 0)
            ->where('status', 'completed')
            ->sum('sale_due');

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('customers.due', compact('sales', 'totalDue', 'currencySymbol'));
    }
}
