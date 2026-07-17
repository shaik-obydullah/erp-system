<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with('details');

        if ($search = $request->input('search')) {
            $query->where('invoice_id', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $sales = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($sales);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('sales.index', compact('sales', 'currencySymbol'));
    }

    public function show(Sale $sale)
    {
        $sale->load('details.stock.product');
        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('sales.show', compact('sale', 'currencySymbol'));
    }

    public function invoice(Sale $sale)
    {
        $sale->load('details.stock.product');
        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('sales.invoice', compact('sale', 'currencySymbol'));
    }
}
