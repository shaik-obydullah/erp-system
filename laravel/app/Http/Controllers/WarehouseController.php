<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
        }

        $warehouses = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($warehouses);
        }

        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('warehouses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'capacity' => 'required|integer|min:0',
            'location' => 'required|string|max:200',
            'contact_number' => 'required|string|max:20',
            'email' => 'required|email',
        ]);

        Warehouse::create([
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'location' => $validated['location'],
            'contact_number' => $validated['contact_number'],
            'email' => $validated['email'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Warehouse', Warehouse::latest()->first());

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse created successfully.');
    }

    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'capacity' => 'required|integer|min:0',
            'location' => 'required|string|max:200',
            'contact_number' => 'required|string|max:20',
            'email' => 'required|email',
        ]);

        $warehouse->update([
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'location' => $validated['location'],
            'contact_number' => $validated['contact_number'],
            'email' => $validated['email'],
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Warehouse', $warehouse, $warehouse->toArray());

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Request $request, Warehouse $warehouse)
    {
        ActivityLogger::deleted('Warehouse', $warehouse);
        $warehouse->update(['deleted_by' => auth('admin')->id()]);
        $warehouse->delete();

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse deleted successfully.');
    }
}
