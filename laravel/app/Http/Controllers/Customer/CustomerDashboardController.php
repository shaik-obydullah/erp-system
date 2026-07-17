<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $customer = Auth::guard('customer')->user();

        return view('customer.dashboard', [
            'customer' => $customer,
            'totalOrders' => 0,
            'totalSpent' => number_format($customer->balance, 2),
            'pendingOrders' => 0,
        ]);
    }
}
