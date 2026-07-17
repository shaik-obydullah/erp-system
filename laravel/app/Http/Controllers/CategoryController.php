<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($parentId = $request->input('fk_category_id')) {
            $query->where('fk_category_id', $parentId);
        }

        $categories = $query->orderBy('name')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($categories);
        }

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        $categories = Category::whereNull('fk_category_id')->orderBy('name')->get();

        return view('categories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'fk_category_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:active,inactive',
        ]);

        Category::create([
            'name' => $validated['name'],
            'url_slug' => Str::slug($validated['name']),
            'fk_category_id' => $validated['fk_category_id'] ?? null,
            'status' => $validated['status'],
            'created_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Category created successfully.',
                'redirect' => route('categories.index'),
            ]);
        }

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $categories = Category::whereNull('fk_category_id')->orderBy('name')->get();

        return view('categories.edit', compact('category', 'categories'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'fk_category_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:active,inactive',
        ]);

        $category->update([
            'name' => $validated['name'],
            'url_slug' => Str::slug($validated['name']),
            'fk_category_id' => $validated['fk_category_id'] ?? null,
            'status' => $validated['status'],
            'updated_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Category updated successfully.',
                'redirect' => route('categories.index'),
            ]);
        }

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Request $request, Category $category)
    {
        $category->update([
            'deleted_by' => auth('admin')->id(),
        ]);

        $category->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Category deleted successfully.',
                'redirect' => route('categories.index'),
            ]);
        }

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
