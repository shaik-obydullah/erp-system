<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $suppliers = $query->orderBy('name')->paginate(15)->withQueryString();

        $currencySymbol = Configuration::get('currency_symbol', '$');

        if ($request->expectsJson()) {
            return response()->json($suppliers);
        }

        return view('suppliers.index', compact('suppliers', 'currencySymbol'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:suppliers,email',
            'password' => 'required|string|min:8|confirmed',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'balance' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        Supplier::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'mobile' => $validated['mobile'] ?? null,
            'address' => $validated['address'] ?? null,
            'balance' => $validated['balance'] ?? 0,
            'status' => $validated['status'],
            'created_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Supplier created successfully.',
                'redirect' => route('suppliers.index'),
            ]);
        }

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    public function show(Supplier $supplier)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('suppliers.show', compact('supplier', 'currencySymbol'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:suppliers,email,'.$supplier->id,
            'password' => 'nullable|string|min:8|confirmed',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'balance' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'] ?? null,
            'address' => $validated['address'] ?? null,
            'balance' => $validated['balance'] ?? 0,
            'status' => $validated['status'],
            'updated_by' => auth('admin')->id(),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $supplier->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Supplier updated successfully.',
                'redirect' => route('suppliers.index'),
            ]);
        }

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        $supplier->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Supplier deleted successfully.',
                'redirect' => route('suppliers.index'),
            ]);
        }

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    public function export()
    {
        $suppliers = Supplier::orderBy('name')->get();

        $filename = 'suppliers_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($suppliers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Email', 'Mobile', 'Address', 'Balance', 'Status']);

            foreach ($suppliers as $supplier) {
                fputcsv($file, [
                    $supplier->name,
                    $supplier->email,
                    $supplier->mobile ?? '',
                    $supplier->address ?? '',
                    $supplier->balance ?? 0,
                    $supplier->status,
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
            if (count($row) < 2) {
                $skipped++;
                continue;
            }

            $data = array_combine(array_slice($header, 0, count($row)), $row);

            $name = trim($data['Name'] ?? '');
            $email = trim($data['Email'] ?? '');

            if (empty($name) || empty($email)) {
                $skipped++;
                continue;
            }

            if (Supplier::where('email', $email)->exists()) {
                $errors[] = "{$email} already exists";
                $skipped++;
                continue;
            }

            try {
                Supplier::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password123'),
                    'mobile' => trim($data['Mobile'] ?? '') ?: null,
                    'address' => trim($data['Address'] ?? '') ?: null,
                    'balance' => (float) ($data['Balance'] ?? 0),
                    'status' => strtolower(trim($data['Status'] ?? 'active')) === 'inactive' ? 'inactive' : 'active',
                    'created_by' => auth('admin')->id(),
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "{$email}: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($file);

        $message = "{$imported} suppliers imported.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped.";
        }
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }

        return redirect()->route('suppliers.index')
            ->with($imported > 0 ? 'success' : 'error', $message);
    }
}
