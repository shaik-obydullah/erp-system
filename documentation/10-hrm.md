# Module 10 — HRM

**Module:** Human Resource Management (HRM)
**Category:** People
**Purpose:** Manages employee records, payroll entries, and task assignments, with payroll posting into the finance ledger.

> HRM is the people layer of the ERP. Employees are standalone records (not linked to auth users), payroll entries calculate net salary and post salary payments, and tasks track work assigned to employees. The dashboard surfaces active employee headcount.

## 1. Overview

The module covers:

- **Employees** — master records with job title, salary, hire date, and status.
- **Payroll** — per-employee pay entries with basic salary, allowances, deductions, net salary, and pay date.
- **Tasks** — work assignments tied to an employee with status, start date, and due date.

There are **no** `departments`, `employee_types`, `attendances`, `leaves`, `leave_types`, `salaries`, or `tasks` tables. Employees are not linked to users and there is no employee-facing portal, attendance tracking, or leave workflow.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `employees` | Employee master | first_name, last_name, email (unique), mobile, job_title, salary, hire_date, status (active/inactive) |
| `payrolls` | Payroll entries | fk_employee_id, basic_salary, allowances, deductions, net_salary, pay_date, journal_entry_id, accounting_version |
| `task_management` | Task assignments | fk_employee_id, task_name, description, status, start_date, due_date |

All three tables track `created_by` / `updated_by` / `deleted_by` and use soft deletes.

### 2.1 Task Status

`pending` → `in_progress` → `completed`, or `canceled`.

## 3. Key Models

- **Employee** (`app/Models/Employee.php`) — `payrolls()`, `tasks()`, `getFullNameAttribute()`; no department/type/user relationships.
- **Payroll** — `employee()`, `journalEntry()`. Table `payrolls`.
- **TaskManagement** — `employee()`. Table `task_management`.

## 4. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `employees` | EmployeeController | resource except `show`; `employees.*` permission group |
| `payrolls` | PayrollController | resource except `show`/`destroy`; `payroll.*` permission group |
| `tasks` | TaskController | resource except `show`; `tasks.*` permission group |

The resource routes currently rely on the `auth:admin` + `active.admin` group and menu visibility; explicit per-route `permission:` middleware is not applied to these CRUD routes.

### 4.1 Employee Features

- Create/edit first name, last name, email, mobile, job title, salary, hire date, and active/inactive status.
- Deleting an employee is blocked when payroll records exist (`EmployeeDeletionGuard`) so salary/accounting history is not orphaned.

### 4.2 Payroll

- Create a payroll entry per employee: `basic_salary`, optional `allowances` and `deductions`, and `pay_date`.
- `net_salary` is computed as `basic_salary + allowances − deductions`.
- Saving posts `AccountingPoster::payroll()`:
  - a `salaryPayment` transaction,
  - a cashbook outflow,
  - a balanced journal entry (salaries expense debit, cash credit).
- There is no payroll status workflow (`pending`/`paid`) and no month/year period columns; the pay date is the period reference.

### 4.3 Tasks

- Assign a task to an employee with `task_name`, description, status, start date, and due date.
- Task status is one of `pending`, `in_progress`, `completed`, `canceled`.

## 5. Employee Portal

There is no employee authentication guard, employee portal, or employee self-service screen in the current codebase. All HRM screens are admin-only.

## 6. HR Reporting

- The dashboard shows the active employee count.
- There are no attendance, leave, headcount-by-department, or payroll-summary reports in the current code; payroll data can be inspected through the payroll list and the finance ledger.

## 7. Permissions

| Group | Permissions |
|---|---|
| Employees | `employees.view/save/edit/delete` |
| Payroll | `payroll.view/save/edit/delete` |
| Tasks | `tasks.view/save/edit/delete` |

## 8. Related Code Locations

- Models: `laravel/app/Models/{Employee,Payroll,TaskManagement}.php`
- Controllers: `laravel/app/Http/Controllers/{EmployeeController,PayrollController,TaskController}.php`
- Services: `laravel/app/Services/EmployeeDeletionGuard.php`
- Accounting: `laravel/app/Accounting/AccountingPoster.php` (`payroll`)
- Views: `laravel/resources/views/hr/`
