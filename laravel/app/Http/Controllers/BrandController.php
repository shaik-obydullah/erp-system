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

    public function export()
    {
        $brands = Brand::orderBy('name')->get();

        $filename = 'brands_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($brands) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Status']);

            foreach ($brands as $brand) {
                fputcsv($file, [
                    $brand->name,
                    $brand->status,
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

            if (Brand::where('name', $name)->exists()) {
                $errors[] = "{$name} already exists";
                $skipped++;
                continue;
            }

            try {
                Brand::create([
                    'name' => $name,
                    'url_slug' => Str::slug($name),
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

        $message = "{$imported} brands imported.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped.";
        }
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }

        return redirect()->route('brands.index')
            ->with($imported > 0 ? 'success' : 'error', $message);
    }
}
