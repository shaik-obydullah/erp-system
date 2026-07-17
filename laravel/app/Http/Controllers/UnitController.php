<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $units = $query->orderBy('name')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($units);
        }

        return view('units.index', compact('units'));
    }

    public function create()
    {
        return view('units.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:units,name',
            'status' => 'required|in:active,inactive',
        ]);

        Unit::create([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'created_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unit created successfully.',
                'redirect' => route('units.index'),
            ]);
        }

        return redirect()->route('units.index')
            ->with('success', 'Unit created successfully.');
    }

    public function edit(Unit $unit)
    {
        return view('units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:units,name,'.$unit->id,
            'status' => 'required|in:active,inactive',
        ]);

        $unit->update([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'updated_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unit updated successfully.',
                'redirect' => route('units.index'),
            ]);
        }

        return redirect()->route('units.index')
            ->with('success', 'Unit updated successfully.');
    }

    public function destroy(Request $request, Unit $unit)
    {
        $unit->update([
            'deleted_by' => auth('admin')->id(),
        ]);

        $unit->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unit deleted successfully.',
                'redirect' => route('units.index'),
            ]);
        }

        return redirect()->route('units.index')
            ->with('success', 'Unit deleted successfully.');
    }
}
