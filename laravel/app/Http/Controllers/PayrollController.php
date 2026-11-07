<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Models\Configuration;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $query = Payroll::with('employee');

        if ($search = $request->input('search')) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $payrolls = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($payrolls);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('payrolls.index', compact('payrolls', 'currencySymbol'));
    }

    public function create()
    {
        $employees = Employee::where('status', 'active')->orderBy('first_name')->get();

        return view('payrolls.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_employee_id' => 'required|exists:employees,id',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'pay_date' => 'required|date',
        ]);

        $allowances = $validated['allowances'] ?? 0;
        $deductions = $validated['deductions'] ?? 0;
        $netSalary = $validated['basic_salary'] + $allowances - $deductions;

        $adminId = auth('admin')->id();

        $payroll = DB::transaction(function () use ($validated, $allowances, $deductions, $netSalary, $adminId) {
            $payroll = Payroll::create([
                'fk_employee_id' => $validated['fk_employee_id'],
                'basic_salary' => $validated['basic_salary'],
                'allowances' => $allowances,
                'deductions' => $deductions,
                'net_salary' => $netSalary,
                'pay_date' => $validated['pay_date'],
                'accounting_version' => 1,
                'created_by' => $adminId,
            ]);

            app(AccountingPoster::class)->payroll($payroll, $adminId);

            return $payroll;
        });

        ActivityLogger::created('Payroll', $payroll);

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll entry created successfully.');
    }

    public function edit(Payroll $payroll)
    {
        $employees = Employee::where('status', 'active')->orderBy('first_name')->get();

        return view('payrolls.edit', compact('payroll', 'employees'));
    }

    public function update(Request $request, Payroll $payroll)
    {
        $validated = $request->validate([
            'fk_employee_id' => 'required|exists:employees,id',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'pay_date' => 'required|date',
        ]);

        $allowances = $validated['allowances'] ?? 0;
        $deductions = $validated['deductions'] ?? 0;
        $netSalary = $validated['basic_salary'] + $allowances - $deductions;

        $adminId = auth('admin')->id();
        $financialChanged = round((float) $payroll->net_salary, 2) !== round((float) $netSalary, 2)
            || $payroll->pay_date?->toDateString() !== $validated['pay_date'];

        DB::transaction(function () use ($payroll, $validated, $allowances, $deductions, $netSalary, $adminId, $financialChanged) {
            $poster = app(AccountingPoster::class);

            if ($financialChanged || (! $payroll->journal_entry_id && $netSalary > 0)) {
                $poster->reverseOperational('payroll', $payroll->id, $adminId);

                $payroll->update([
                    'fk_employee_id' => $validated['fk_employee_id'],
                    'basic_salary' => $validated['basic_salary'],
                    'allowances' => $allowances,
                    'deductions' => $deductions,
                    'net_salary' => $netSalary,
                    'pay_date' => $validated['pay_date'],
                    'journal_entry_id' => null,
                    'accounting_version' => max(1, (int) $payroll->accounting_version) + 1,
                    'updated_by' => $adminId,
                ]);

                $poster->payroll($payroll->fresh(), $adminId);

                return;
            }

            $payroll->update([
                'fk_employee_id' => $validated['fk_employee_id'],
                'basic_salary' => $validated['basic_salary'],
                'allowances' => $allowances,
                'deductions' => $deductions,
                'net_salary' => $netSalary,
                'pay_date' => $validated['pay_date'],
                'updated_by' => $adminId,
            ]);
        });

        ActivityLogger::updated('Payroll', $payroll, $payroll->toArray());

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll entry updated successfully.');
    }

    public function destroy(Request $request, Payroll $payroll)
    {
        $adminId = auth('admin')->id();

        DB::transaction(function () use ($payroll, $adminId) {
            app(AccountingPoster::class)->reverseOperational('payroll', $payroll->id, $adminId);

            $payroll->update(['deleted_by' => $adminId, 'journal_entry_id' => null]);
            $payroll->delete();
        });

        ActivityLogger::deleted('Payroll', $payroll);

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll entry deleted successfully.');
    }
}
