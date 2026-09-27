<?php

namespace App\Http\Controllers;

use App\Models\Cashbook;
use App\Models\Configuration;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Cashbook::where('table_name', 'suppliers')
            ->with('supplier');

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('fk_reference_id', $supplierId);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('suppliers.transaction', compact('transactions', 'suppliers', 'currencySymbol'));
    }

    public function export(Request $request)
    {
        $query = Cashbook::where('table_name', 'suppliers')->with('supplier');

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('fk_reference_id', $supplierId);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderByDesc('id')->get();

        $filename = 'supplier_transactions_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Supplier', 'Description', 'In Amount', 'Out Amount', 'Payable', 'Receivable', 'Date']);

            foreach ($transactions as $txn) {
                fputcsv($file, [
                    $txn->id,
                    $txn->supplier->name ?? 'N/A',
                    $txn->description ?? '',
                    $txn->in_amount ?? 0,
                    $txn->out_amount ?? 0,
                    $txn->amount_payable ?? 0,
                    $txn->amount_receivable ?? 0,
                    $txn->created_at ? $txn->created_at->format('Y-m-d') : '',
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

            $supplierName = trim($data['Supplier'] ?? '');

            if (empty($supplierName)) {
                $skipped++;
                continue;
            }

            $supplier = Supplier::where('name', $supplierName)->first();

            if (! $supplier) {
                $errors[] = "Supplier '{$supplierName}' not found";
                $skipped++;
                continue;
            }

            try {
                Cashbook::create([
                    'table_name' => 'suppliers',
                    'fk_reference_id' => $supplier->id,
                    'description' => trim($data['Description'] ?? '') ?: null,
                    'in_amount' => (float) ($data['In Amount'] ?? 0),
                    'out_amount' => (float) ($data['Out Amount'] ?? 0),
                    'amount_payable' => (float) ($data['Payable'] ?? 0),
                    'amount_receivable' => (float) ($data['Receivable'] ?? 0),
                    'created_by' => auth('admin')->id(),
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "{$supplierName}: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($file);

        $message = "{$imported} transactions imported.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped.";
        }
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }

        return redirect()->route('suppliers.transaction.index')
            ->with($imported > 0 ? 'success' : 'error', $message);
    }
}
