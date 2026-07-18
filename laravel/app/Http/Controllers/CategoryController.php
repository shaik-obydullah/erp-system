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

    public function export()
    {
        $categories = Category::with('parent')->orderBy('name')->get();

        $filename = 'categories_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($categories) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Parent Category', 'Status']);

            foreach ($categories as $category) {
                fputcsv($file, [
                    $category->name,
                    $category->parent->name ?? '',
                    $category->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = fopen($request->file('csv_file')->getPathname(), 'r');
        $header = fgetcsv($file);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 1) {
                $skipped++;
                continue;
            }

            $data = array_combine(array_slice($header, 0, count($row)), $row);

            $name = trim($data['Name'] ?? '');

            if (empty($name)) {
                $skipped++;
                continue;
            }

            if (Category::where('name', $name)->exists()) {
                $errors[] = "{$name} already exists";
                $skipped++;
                continue;
            }

            $parentId = null;
            $parentName = trim($data['Parent Category'] ?? '');
            if (!empty($parentName)) {
                $parent = Category::where('name', $parentName)->first();
                if ($parent) {
                    $parentId = $parent->id;
                } else {
                    $errors[] = "Parent category '{$parentName}' not found for '{$name}'";
                }
            }

            try {
                Category::create([
                    'name' => $name,
                    'url_slug' => Str::slug($name),
                    'fk_category_id' => $parentId,
                    'status' => strtolower(trim($data['Status'] ?? 'active')) === 'inactive' ? 'inactive' : 'active',
                    'created_by' => auth('admin')->id(),
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "{$name}: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($file);

        $message = "{$imported} categories imported.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped.";
        }
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }

        return redirect()->route('categories.index')
            ->with($imported > 0 ? 'success' : 'error', $message);
    }
}
