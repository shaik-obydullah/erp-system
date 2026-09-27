<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TaskManagement;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = TaskManagement::with('employee');

        if ($search = $request->input('search')) {
            $query->where('task_name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $tasks = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($tasks);
        }

        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        $employees = Employee::where('status', 'active')->orderBy('first_name')->get();

        return view('tasks.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_employee_id' => 'required|exists:employees,id',
            'task_name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,canceled',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
        ]);

        TaskManagement::create([
            'fk_employee_id' => $validated['fk_employee_id'],
            'task_name' => $validated['task_name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'start_date' => $validated['start_date'],
            'due_date' => $validated['due_date'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Task', TaskManagement::latest('id')->first());

        return redirect()->route('tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function edit(TaskManagement $task)
    {
        $employees = Employee::where('status', 'active')->orderBy('first_name')->get();

        return view('tasks.edit', compact('task', 'employees'));
    }

    public function update(Request $request, TaskManagement $task)
    {
        $validated = $request->validate([
            'fk_employee_id' => 'required|exists:employees,id',
            'task_name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,canceled',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
        ]);

        $task->update([
            'fk_employee_id' => $validated['fk_employee_id'],
            'task_name' => $validated['task_name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'start_date' => $validated['start_date'],
            'due_date' => $validated['due_date'],
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Task', $task, $task->toArray());

        return redirect()->route('tasks.index')
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Request $request, TaskManagement $task)
    {
        ActivityLogger::deleted('Task', $task);
        $task->update(['deleted_by' => auth('admin')->id()]);
        $task->delete();

        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }
}
