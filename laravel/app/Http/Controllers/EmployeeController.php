<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Employee;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $employees = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($employees);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('employees.index', compact('employees', 'currencySymbol'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:employees,email',
            'mobile' => 'nullable|string|max:20',
            'job_title' => 'required|string|max:100',
            'salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
            'status' => 'required|in:active,inactive',
        ]);

        Employee::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'] ?? null,
            'job_title' => $validated['job_title'],
            'salary' => $validated['salary'],
            'hire_date' => $validated['hire_date'],
            'status' => $validated['status'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Employee', Employee::latest('id')->first());

        return redirect()->route('employees.index')
            ->with('success', 'Employee created successfully.');
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:employees,email,' . $employee->id,
            'mobile' => 'nullable|string|max:20',
            'job_title' => 'required|string|max:100',
            'salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
            'status' => 'required|in:active,inactive',
        ]);

        $employee->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'] ?? null,
            'job_title' => $validated['job_title'],
            'salary' => $validated['salary'],
            'hire_date' => $validated['hire_date'],
            'status' => $validated['status'],
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Employee', $employee, $employee->toArray());

        return redirect()->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Request $request, Employee $employee)
    {
        ActivityLogger::deleted('Employee', $employee);
        $employee->update(['deleted_by' => auth('admin')->id()]);
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }
}
