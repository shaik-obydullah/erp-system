<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerPortalController extends Controller
{
    public function dashboard()
    {
        $customer = Auth::guard('customer')->user();

        $totalOrders = Sale::where('fk_user_id', $customer->id)->count();
        $totalSpent = Sale::where('fk_user_id', $customer->id)->sum('grand_total');
        $pendingOrders = Sale::where('fk_user_id', $customer->id)->where('status', 'pending')->count();
        $balance = $customer->balance;

        $recentOrders = Sale::where('fk_user_id', $customer->id)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        return view('customer-portal.dashboard', compact(
            'customer', 'totalOrders', 'totalSpent', 'pendingOrders', 'balance', 'recentOrders'
        ));
    }

    public function orders(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $query = Sale::where('fk_user_id', $customer->id);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('customer-portal.orders', compact('customer', 'orders'));
    }

    public function orderDetail($id)
    {
        $customer = Auth::guard('customer')->user();

        $order = Sale::where('fk_user_id', $customer->id)
            ->with(['details', 'details.stock'])
            ->findOrFail($id);

        return view('customer-portal.order-detail', compact('customer', 'order'));
    }

    public function profile()
    {
        $customer = Auth::guard('customer')->user();

        return view('customer-portal.profile', compact('customer'));
    }

    public function updateProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:customers,email,'.$customer->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'updated_by' => $customer->id,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $customer->update($data);

        return redirect()->route('portal.profile')
            ->with('success', 'Profile updated successfully.');
    }
}
