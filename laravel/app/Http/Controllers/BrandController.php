<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $brands = $query->orderBy('name')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($brands);
        }

        return view('brands.index', compact('brands'));
    }

    public function create()
    {
        return view('brands.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        Brand::create([
            'name' => $validated['name'],
            'url_slug' => Str::slug($validated['name']),
            'status' => $validated['status'],
            'created_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Brand created successfully.',
                'redirect' => route('brands.index'),
            ]);
        }

        return redirect()->route('brands.index')
            ->with('success', 'Brand created successfully.');
    }

    public function edit(Brand $brand)
    {
        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $brand->update([
            'name' => $validated['name'],
            'url_slug' => Str::slug($validated['name']),
            'status' => $validated['status'],
            'updated_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Brand updated successfully.',
                'redirect' => route('brands.index'),
            ]);
        }

        return redirect()->route('brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    public function destroy(Request $request, Brand $brand)
    {
        $brand->update([
            'deleted_by' => auth('admin')->id(),
        ]);

        $brand->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Brand deleted successfully.',
                'redirect' => route('brands.index'),
            ]);
        }

        return redirect()->route('brands.index')
            ->with('success', 'Brand deleted successfully.');
    }
}
