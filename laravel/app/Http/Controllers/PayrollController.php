<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

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

        Payroll::create([
            'fk_employee_id' => $validated['fk_employee_id'],
            'basic_salary' => $validated['basic_salary'],
            'allowances' => $allowances,
            'deductions' => $deductions,
            'net_salary' => $netSalary,
            'pay_date' => $validated['pay_date'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Payroll', Payroll::latest()->first());

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

        $payroll->update([
            'fk_employee_id' => $validated['fk_employee_id'],
            'basic_salary' => $validated['basic_salary'],
            'allowances' => $allowances,
            'deductions' => $deductions,
            'net_salary' => $netSalary,
            'pay_date' => $validated['pay_date'],
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Payroll', $payroll, $payroll->toArray());

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll entry updated successfully.');
    }

    public function destroy(Request $request, Payroll $payroll)
    {
        ActivityLogger::deleted('Payroll', $payroll);
        $payroll->update(['deleted_by' => auth('admin')->id()]);
        $payroll->delete();

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll entry deleted successfully.');
    }
}
