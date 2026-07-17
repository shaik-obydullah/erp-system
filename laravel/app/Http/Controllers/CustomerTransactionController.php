<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Cashbook;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Cashbook::where('table_name', 'customers')
            ->with('reference');

        if ($customerId = $request->input('customer_id')) {
            $query->where('fk_reference_id', $customerId);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('customers.transaction', compact('transactions', 'customers', 'currencySymbol'));
    }
}
