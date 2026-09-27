<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SupplierPortalController extends Controller
{
    public function dashboard()
    {
        $supplier = Auth::guard('supplier')->user();

        $totalProducts = Product::where('fk_supplier_id', $supplier->id)
            ->where('status', 'active')
            ->count();

        $totalPOs = PurchaseOrder::where('fk_supplier_id', $supplier->id)->count();
        $pendingPOs = PurchaseOrder::where('fk_supplier_id', $supplier->id)
            ->where('due_amount', '>', 0)
            ->count();
        $balance = $supplier->balance;

        $recentPOs = PurchaseOrder::where('fk_supplier_id', $supplier->id)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        return view('supplier-portal.dashboard', compact(
            'supplier', 'totalProducts', 'totalPOs', 'pendingPOs', 'balance', 'recentPOs'
        ));
    }

    public function purchaseOrders(Request $request)
    {
        $supplier = Auth::guard('supplier')->user();

        $query = PurchaseOrder::where('fk_supplier_id', $supplier->id);

        if ($status = $request->input('status')) {
            if ($status === 'paid') {
                $query->where('due_amount', '<=', 0);
            } elseif ($status === 'pending') {
                $query->where('due_amount', '>', 0);
            }
        }

        $orders = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('supplier-portal.purchase-orders', compact('supplier', 'orders'));
    }

    public function poDetail($id)
    {
        $supplier = Auth::guard('supplier')->user();

        $order = PurchaseOrder::where('fk_supplier_id', $supplier->id)
            ->with(['need'])
            ->findOrFail($id);

        return view('supplier-portal.po-detail', compact('supplier', 'order'));
    }

    public function products(Request $request)
    {
        $supplier = Auth::guard('supplier')->user();

        $query = Product::where('fk_supplier_id', $supplier->id);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('supplier-portal.products', compact('supplier', 'products'));
    }

    public function profile()
    {
        $supplier = Auth::guard('supplier')->user();

        return view('supplier-portal.profile', compact('supplier'));
    }

    public function updateProfile(Request $request)
    {
        $supplier = Auth::guard('supplier')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:suppliers,email,'.$supplier->id,
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'] ?? null,
            'address' => $validated['address'] ?? null,
            'updated_by' => $supplier->id,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $supplier->update($data);

        return redirect()->route('supplier-portal.profile')
            ->with('success', 'Profile updated successfully.');
    }
}
