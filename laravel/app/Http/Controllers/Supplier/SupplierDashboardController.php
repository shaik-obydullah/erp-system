<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupplierDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $supplier = Auth::guard('supplier')->user();

        return view('supplier.dashboard', [
            'supplier' => $supplier,
            'totalOrders' => 0,
            'totalPaid' => number_format($supplier->balance, 2),
            'pendingPayments' => 0,
        ]);
    }
}
