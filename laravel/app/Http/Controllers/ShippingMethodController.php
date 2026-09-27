<?php

namespace App\Http\Controllers;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Http\Request;

class ShippingMethodController extends Controller
{
    public function store(Request $request, ShippingZone $shippingZone)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:flat_rate,free_shipping,local_pickup',
            'cost' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        ShippingMethod::create([
            'fk_shipping_zone_id' => $shippingZone->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'cost' => (float) ($validated['cost'] ?? 0),
            'min_order_amount' => $validated['type'] === 'free_shipping'
                ? (float) ($validated['min_order_amount'] ?? 0)
                : ($validated['min_order_amount'] ?? null),
            'status' => $validated['status'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'created_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Shipping method created successfully.',
                'redirect' => route('shipping-zones.edit', $shippingZone),
            ]);
        }

        return redirect()->route('shipping-zones.edit', $shippingZone)
            ->with('success', 'Shipping method created successfully.');
    }

    public function update(Request $request, ShippingZone $shippingZone, ShippingMethod $shippingMethod)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:flat_rate,free_shipping,local_pickup',
            'cost' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        $shippingMethod->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'cost' => (float) ($validated['cost'] ?? 0),
            'min_order_amount' => $validated['type'] === 'free_shipping'
                ? (float) ($validated['min_order_amount'] ?? 0)
                : ($validated['min_order_amount'] ?? null),
            'status' => $validated['status'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'updated_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Shipping method updated successfully.',
                'redirect' => route('shipping-zones.edit', $shippingZone),
            ]);
        }

        return redirect()->route('shipping-zones.edit', $shippingZone)
            ->with('success', 'Shipping method updated successfully.');
    }

    public function destroy(Request $request, ShippingZone $shippingZone, ShippingMethod $shippingMethod)
    {
        $shippingMethod->update([
            'deleted_by' => auth('admin')->id(),
        ]);

        $shippingMethod->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Shipping method deleted successfully.',
                'redirect' => route('shipping-zones.edit', $shippingZone),
            ]);
        }

        return redirect()->route('shipping-zones.edit', $shippingZone)
            ->with('success', 'Shipping method deleted successfully.');
    }
}