<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $customers = $query->orderBy('name')->paginate(15)->withQueryString();

        $currencySymbol = Configuration::get('currency_symbol', '$');

        if ($request->expectsJson()) {
            return response()->json($customers);
        }

        return view('customers.index', compact('customers', 'currencySymbol'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:customers,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
            'created_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Customer created successfully.',
                'redirect' => route('customers.index'),
            ]);
        }

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('customers.show', compact('customer', 'currencySymbol'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:customers,email,'.$customer->id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
            'updated_by' => auth('admin')->id(),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $customer->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Customer updated successfully.',
                'redirect' => route('customers.index'),
            ]);
        }

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Request $request, Customer $customer)
    {
        $customer->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Customer deleted successfully.',
                'redirect' => route('customers.index'),
            ]);
        }

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    public function export()
    {
        $customers = Customer::orderBy('name')->get();

        $filename = 'customers_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($customers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Email', 'Phone', 'Address', 'Balance', 'Status']);

            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->name,
                    $customer->email,
                    $customer->phone ?? '',
                    $customer->address ?? '',
                    $customer->balance ?? 0,
                    $customer->status,
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

            if (Customer::where('email', $email)->exists()) {
                $errors[] = "{$email} already exists";
                $skipped++;
                continue;
            }

            try {
                Customer::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password123'),
                    'phone' => trim($data['Phone'] ?? '') ?: null,
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

        $message = "{$imported} customers imported.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped.";
        }
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }

        return redirect()->route('customers.index')
            ->with($imported > 0 ? 'success' : 'error', $message);
    }
}
