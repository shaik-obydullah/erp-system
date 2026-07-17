<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function index(Request $request)
    {
        $query = Color::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $colors = $query->orderBy('name')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($colors);
        }

        return view('colors.index', compact('colors'));
    }

    public function create()
    {
        return view('colors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:colors,name',
            'status' => 'required|in:active,inactive',
        ]);

        Color::create([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'created_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Color created successfully.',
                'redirect' => route('colors.index'),
            ]);
        }

        return redirect()->route('colors.index')
            ->with('success', 'Color created successfully.');
    }

    public function edit(Color $color)
    {
        return view('colors.edit', compact('color'));
    }

    public function update(Request $request, Color $color)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:colors,name,'.$color->id,
            'status' => 'required|in:active,inactive',
        ]);

        $color->update([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'updated_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Color updated successfully.',
                'redirect' => route('colors.index'),
            ]);
        }

        return redirect()->route('colors.index')
            ->with('success', 'Color updated successfully.');
    }

    public function destroy(Request $request, Color $color)
    {
        $color->update([
            'deleted_by' => auth('admin')->id(),
        ]);

        $color->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Color deleted successfully.',
                'redirect' => route('colors.index'),
            ]);
        }

        return redirect()->route('colors.index')
            ->with('success', 'Color deleted successfully.');
    }
}
