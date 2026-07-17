<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerDueController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::where('balance', '<', 0)
            ->where('status', 'active');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('balance')->paginate(15)->withQueryString();

        $totalDue = Customer::where('balance', '<', 0)
            ->where('status', 'active')
            ->sum('balance');

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('customers.due', compact('customers', 'totalDue', 'currencySymbol'));
    }
}
