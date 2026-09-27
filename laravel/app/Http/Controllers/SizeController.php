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

    public function export()
    {
        $items = Size::orderBy('name')->get();
        $filename = 'sizes_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        $callback = function () use ($items) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Status']);
            foreach ($items as $item) {
                fputcsv($file, [$item->name, $item->status]);
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

            if (Size::where('name', $name)->exists()) {
                $errors[] = "{$name} already exists";
                $skipped++;
                continue;
            }

            try {
                Size::create([
                    'name' => $name,
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

        $message = "{$imported} sizes imported.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped.";
        }
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }

        return redirect()->route('sizes.index')
            ->with($imported > 0 ? 'success' : 'error', $message);
    }
}
