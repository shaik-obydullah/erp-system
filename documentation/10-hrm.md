# Module 10 — HRM

**Module:** Human Resource Management (HRM)
**Category:** People
**Purpose:** Manages the workforce: employees, attendance, leave, payroll, tasks, and the employee-facing portal.

> HRM is the people layer of the ERP. Employees are linked to users, attendance feeds payroll calculations, and task assignments keep operations running. Reports draw headcount, leave, and payroll summaries for management.

## 1. Overview

The module covers:

- **Employees** — master records with job details and user linkage.
- **Attendance** — daily check-in/check-out tracking.
- **Leave** — leave types, balances, and approvals.
- **Payroll** — pay calculations per period with per-employee details.
- **Tasks** — assignments, priorities, statuses, and due dates.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `employees` | Employee master | name, fk_department_id, fk_employee_type_id, fk_user_id, designation, sex, email, phone, dob, joining_date, basic_salary, image, status |
| `employee_types` | Job type reference | name, status |
| `departments` | Department reference | name, status |
| `attendances` | Daily attendance | fk_employee_id, in_time, out_time, status (present/absent/leave/holiday), note |
| `leaves` | Leave requests | fk_employee_id, fk_leave_type_id, from_date, to_date, days, reason, status (pending/approved/rejected) |
| `leave_types` | Leave categories | name, max_days, status |
| `salaries` | Payroll run | fk_employee_id, fk_admin_id, basic_salary, tax_amount, deduction_amount, bonus_amount, total_amount, note, status, month, year |
| `tasks` | Work assignments | fk_employee_id, task_title, task_type, description, priority, status, due_date, fk_admin_id |

### 2.1 Attendance Status

`present` / `absent` / `leave` / `holiday` — drives attendance reports and payroll day counts.

### 2.2 Leave Status Lifecycle

```
pending → approved | rejected
```

### 2.3 Payroll Status

`pending` → `paid` (with month/year period stamps).

## 3. Key Models

- **Employee** (`app/Models/Employee.php`) — `department()`, `employeeType()`, `user()` (linked admin/user account), `attendances()`, `leaves()`, `salaries()`, `tasks()`. Scopes: `active`.
- **Department** — `employees()`.
- **LeaveType** — `max_days` cap; `leaves()`.
- **Salary** — per-employee, per-month row; total = basic ± bonus − tax − deductions.
- **Task** — `employee()`, `admin()`; priority (low/medium/high) + status + due date.

## 4. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `employees` | EmployeeController | employees.{view,save,edit,delete} |
| `departments` | DepartmentController | departments.{view,save,edit,delete} |
| `employee-types` | EmployeeTypeController | employeetype.{view,save,edit,delete} |
| `attendances` | AttendanceController | attendances.{view,save,edit,delete} |
| `leaves` | LeaveController | leaves.{view,save,edit,delete} |
| `salaries` | SalaryController | payroll.{view,save,edit,delete} |
| `tasks` | TaskController | tasks.{view,save,edit,delete} |

### 4.1 Employee Features

- Master data with designation, department, employment type, salary, DOB, joining date, and linked user account.
- Active/inactive status toggles portal access.

### 4.2 Attendance

- Mark present/absent/leave/holiday per employee per day; in/out times recorded.
- Period summaries feed payroll day calculations.

### 4.3 Payroll

- Per-month run per employee: basic salary ± bonus − tax − deduction; status pending → paid.
- Monthly report groups employees by month/year with totals.

### 4.4 Tasks

- Assign tasks with title, type, description, priority, due date; track status to completion.

## 5. Employee Portal

Authenticated employees (linked `user` accounts) can view their own attendance, leave balance/requests, payslips, and assigned tasks.

## 6. HR Reports

- Headcount by department/type.
- Monthly attendance summary (present/absent/leave counts per employee).
- Leave utilization per type.
- Payroll totals per month (gross, tax, deductions, net).

## 7. Permissions

| Group | Permissions |
|---|---|
| Employees | `employees.view/save/edit/delete` |
| Departments | `departments.view/save/edit/delete` |
| Employee Types | `employeetype.view/save/edit/delete` |
| Attendances | `attendances.view/save/edit/delete` |
| Leaves | `leaves.view/save/edit/delete` |
| Payroll | `payroll.view/save/edit/delete` |
| Tasks | `tasks.view/save/edit/delete` |

## 8. Related Code Locations

- Models: `laravel/app/Models/{Employee,Department,EmployeeType,Attendance,Leave,LeaveType,Salary,Task}.php`
- Controllers: `laravel/app/Http/Controllers/{EmployeeController,DepartmentController,EmployeeTypeController,AttendanceController,LeaveController,SalaryController,TaskController}.php`
- Views: `laravel/resources/views/hr/`
