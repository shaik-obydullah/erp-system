<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierDueController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::where('balance', '<', 0)
            ->where('status', 'active');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('balance')->paginate(15)->withQueryString();

        $totalDue = Supplier::where('balance', '<', 0)
            ->where('status', 'active')
            ->sum('balance');

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('suppliers.due', compact('suppliers', 'totalDue', 'currencySymbol'));
    }
}
