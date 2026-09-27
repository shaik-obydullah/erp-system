<?php

namespace App\Http\Controllers;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Http\Request;

class ShippingZoneController extends Controller
{
    public function index(Request $request)
    {
        $query = ShippingZone::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $zones = $query->withCount('methods')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($zones);
        }

        return view('shipping-zones.index', compact('zones'));
    }

    public function create()
    {
        $countries = config('countries');

        return view('shipping-zones.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'countries' => 'nullable|array',
            'countries.*' => 'string|size:2',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        $zone = ShippingZone::create([
            'name' => $validated['name'],
            'countries' => !empty($validated['countries'])
                ? json_encode($validated['countries'])
                : ($request->boolean('rest_of_world') ? json_encode(['*']) : null),
            'status' => $validated['status'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'created_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Shipping zone created successfully.',
                'redirect' => route('shipping-zones.edit', $zone),
            ]);
        }

        return redirect()->route('shipping-zones.edit', $zone)
            ->with('success', 'Shipping zone created successfully. Now add shipping methods.');
    }

    public function edit(ShippingZone $shippingZone)
    {
        $countries = config('countries');

        return view('shipping-zones.edit', compact('shippingZone', 'countries'));
    }

    public function update(Request $request, ShippingZone $shippingZone)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'countries' => 'nullable|array',
            'countries.*' => 'string|size:2',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        $shippingZone->update([
            'name' => $validated['name'],
            'countries' => !empty($validated['countries'])
                ? json_encode($validated['countries'])
                : ($request->boolean('rest_of_world') ? json_encode(['*']) : null),
            'status' => $validated['status'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'updated_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Shipping zone updated successfully.',
                'redirect' => route('shipping-zones.edit', $shippingZone),
            ]);
        }

        return redirect()->route('shipping-zones.edit', $shippingZone)
            ->with('success', 'Shipping zone updated successfully.');
    }

    public function destroy(Request $request, ShippingZone $shippingZone)
    {
        $shippingZone->update([
            'deleted_by' => auth('admin')->id(),
        ]);

        $shippingZone->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Shipping zone deleted successfully.',
                'redirect' => route('shipping-zones.index'),
            ]);
        }

        return redirect()->route('shipping-zones.index')
            ->with('success', 'Shipping zone deleted successfully.');
    }
}