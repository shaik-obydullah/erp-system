<?php

namespace App\Http\Controllers;

use App\Models\Cashbook;
use App\Models\Configuration;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Cashbook::where('table_name', 'suppliers')
            ->with('reference');

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('fk_reference_id', $supplierId);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('suppliers.transaction', compact('transactions', 'suppliers', 'currencySymbol'));
    }
}
