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

    public function export()
    {
        $units = Unit::orderBy('name')->get();

        $filename = 'units_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($units) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Status']);

            foreach ($units as $unit) {
                fputcsv($file, [
                    $unit->name,
                    $unit->status,
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

            if (Unit::where('name', $name)->exists()) {
                $errors[] = "{$name} already exists";
                $skipped++;
                continue;
            }

            try {
                Unit::create([
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

        $message = "{$imported} units imported.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped.";
        }
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }

        return redirect()->route('units.index')
            ->with($imported > 0 ? 'success' : 'error', $message);
    }
}
