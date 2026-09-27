@extends('roles.layout')

@section('title', 'Create Payroll')

@section('content')
<div class="card" x-data="formHandler('{{ route('payrolls.store') }}', 'POST')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Create Payroll Entry</h2>
        <a href="{{ route('payrolls.index') }}" class="btn btn-secondary">Back to Payrolls</a>
    </div>
    <div class="card-body">

        <div x-show="successMessage" x-cloak class="alert alert-success show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span x-text="successMessage"></span>
        </div>

        <div x-show="errorMessage" x-cloak class="alert alert-error show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <span x-text="errorMessage"></span>
        </div>

        <form @submit.prevent="submit($event)">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label for="fk_employee_id">Employee *</label>
                    <select id="fk_employee_id" x-model="form.fk_employee_id" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_employee_id" x-text="errors.fk_employee_id"></span>
                </div>
                <div class="form-group">
                    <label for="pay_date">Pay Date *</label>
                    <input type="date" id="pay_date" x-model="form.pay_date" required>
                    <span class="form-error" x-show="errors.pay_date" x-text="errors.pay_date"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="basic_salary">Basic Salary *</label>
                    <input type="number" id="basic_salary" x-model="form.basic_salary" step="0.01" min="0" required>
                    <span class="form-error" x-show="errors.basic_salary" x-text="errors.basic_salary"></span>
                </div>
                <div class="form-group">
                    <label for="allowances">Allowances</label>
                    <input type="number" id="allowances" x-model="form.allowances" step="0.01" min="0">
                    <span class="form-error" x-show="errors.allowances" x-text="errors.allowances"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="deductions">Deductions</label>
                    <input type="number" id="deductions" x-model="form.deductions" step="0.01" min="0">
                    <span class="form-error" x-show="errors.deductions" x-text="errors.deductions"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Create Payroll</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('payrolls.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                fk_employee_id: '',
                basic_salary: '',
                allowances: '',
                deductions: '',
                pay_date: '',
            },
            errors: {},
            errorMessage: '',
            successMessage: '',
            submitting: false,
            csrfToken: '',
            init() {
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            },
            async submit() {
                this.errors = {};
                this.errorMessage = '';
                this.successMessage = '';
                this.submitting = true;
                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.form),
                    });
                    const data = await response.json();
                    if (response.ok) {
                        window.location.href = data.redirect || '{{ route("payrolls.index") }}';
                        return;
                    }
                    if (data.errors) {
                        this.errors = {};
                        for (const key in data.errors) {
                            this.errors[key] = data.errors[key][0];
                        }
                    } else if (data.message) {
                        this.errorMessage = data.message;
                    }
                } catch (e) {
                    this.errorMessage = 'An unexpected error occurred. Please try again.';
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>
@endsection
