<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function index(Request $request)
    {
        $query = Size::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sizes = $query->orderBy('name')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($sizes);
        }

        return view('sizes.index', compact('sizes'));
    }

    public function create()
    {
        return view('sizes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:sizes,name',
            'status' => 'required|in:active,inactive',
        ]);

        Size::create([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'created_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Size created successfully.',
                'redirect' => route('sizes.index'),
            ]);
        }

        return redirect()->route('sizes.index')
            ->with('success', 'Size created successfully.');
    }

    public function edit(Size $size)
    {
        return view('sizes.edit', compact('size'));
    }

    public function update(Request $request, Size $size)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:sizes,name,'.$size->id,
            'status' => 'required|in:active,inactive',
        ]);

        $size->update([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'updated_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Size updated successfully.',
                'redirect' => route('sizes.index'),
            ]);
        }

        return redirect()->route('sizes.index')
            ->with('success', 'Size updated successfully.');
    }

    public function destroy(Request $request, Size $size)
    {
        $size->update([
            'deleted_by' => auth('admin')->id(),
        ]);

        $size->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Size deleted successfully.',
                'redirect' => route('sizes.index'),
            ]);
        }

        return redirect()->route('sizes.index')
            ->with('success', 'Size deleted successfully.');
    }
}
