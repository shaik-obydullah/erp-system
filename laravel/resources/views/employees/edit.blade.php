@extends('roles.layout')

@section('title', 'Edit Employee')

@section('content')
<div class="card" x-data="formHandler('{{ route('employees.update', $employee) }}', 'PUT')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Edit Employee</h2>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Back to Employees</a>
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
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" x-model="form.first_name" value="{{ old('first_name', $employee->first_name) }}" required autofocus>
                    <span class="form-error" x-show="errors.first_name" x-text="errors.first_name"></span>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" x-model="form.last_name" value="{{ old('last_name', $employee->last_name) }}" required>
                    <span class="form-error" x-show="errors.last_name" x-text="errors.last_name"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" x-model="form.email" value="{{ old('email', $employee->email) }}" required>
                    <span class="form-error" x-show="errors.email" x-text="errors.email"></span>
                </div>
                <div class="form-group">
                    <label for="mobile">Mobile</label>
                    <input type="text" id="mobile" x-model="form.mobile" value="{{ old('mobile', $employee->mobile) }}">
                    <span class="form-error" x-show="errors.mobile" x-text="errors.mobile"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="job_title">Job Title</label>
                    <input type="text" id="job_title" x-model="form.job_title" value="{{ old('job_title', $employee->job_title) }}">
                    <span class="form-error" x-show="errors.job_title" x-text="errors.job_title"></span>
                </div>
                <div class="form-group">
                    <label for="salary">Salary *</label>
                    <input type="number" id="salary" x-model="form.salary" value="{{ old('salary', $employee->salary) }}" step="0.01" min="0" required>
                    <span class="form-error" x-show="errors.salary" x-text="errors.salary"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="hire_date">Hire Date</label>
                    <input type="date" id="hire_date" x-model="form.hire_date" value="{{ old('hire_date', $employee->hire_date) }}">
                    <span class="form-error" x-show="errors.hire_date" x-text="errors.hire_date"></span>
                </div>
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" x-model="form.status" required>
                        <option value="active" {{ $employee->status == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $employee->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="terminated" {{ $employee->status == 'terminated' ? 'selected' : '' }}>Terminated</option>
                    </select>
                    <span class="form-error" x-show="errors.status" x-text="errors.status"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Update Employee</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                first_name: '{{ old('first_name', $employee->first_name) }}',
                last_name: '{{ old('last_name', $employee->last_name) }}',
                email: '{{ old('email', $employee->email) }}',
                mobile: '{{ old('mobile', $employee->mobile) }}',
                job_title: '{{ old('job_title', $employee->job_title) }}',
                salary: '{{ old('salary', $employee->salary) }}',
                hire_date: '{{ old('hire_date', $employee->hire_date) }}',
                status: '{{ old('status', $employee->status) }}',
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
                            'X-HTTP-Method-Override': method,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.form),
                    });
                    const data = await response.json();
                    if (response.ok) {
                        window.location.href = data.redirect || '{{ route("employees.index") }}';
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
