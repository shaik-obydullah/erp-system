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

    public function export()
    {
        $items = Color::orderBy('name')->get();
        $filename = 'colors_' . now()->format('Y-m-d_His') . '.csv';
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

            if (Color::where('name', $name)->exists()) {
                $errors[] = "{$name} already exists";
                $skipped++;
                continue;
            }

            try {
                Color::create([
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

        $message = "{$imported} colors imported.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped.";
        }
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }

        return redirect()->route('colors.index')
            ->with($imported > 0 ? 'success' : 'error', $message);
    }
}
