@extends('roles.layout')

@section('title', 'Edit Campaign')

@section('content')
<div class="card" x-data="formHandler('{{ route('campaigns.update', $campaign) }}', 'PUT')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Edit Campaign</h2>
        <a href="{{ route('campaigns.index') }}" class="btn btn-secondary">Back to Campaigns</a>
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
                    <label for="name">Name *</label>
                    <input type="text" id="name" x-model="form.name" value="{{ old('name', $campaign->name) }}" required autofocus>
                    <span class="form-error" x-show="errors.name" x-text="errors.name"></span>
                </div>
                <div class="form-group">
                    <label for="budget">Budget *</label>
                    <input type="number" id="budget" x-model="form.budget" value="{{ old('budget', $campaign->budget) }}" step="0.01" min="0" required>
                    <span class="form-error" x-show="errors.budget" x-text="errors.budget"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" x-model="form.description" rows="4">{{ old('description', $campaign->description) }}</textarea>
                    <span class="form-error" x-show="errors.description" x-text="errors.description"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" x-model="form.start_date" value="{{ old('start_date', $campaign->start_date) }}">
                    <span class="form-error" x-show="errors.start_date" x-text="errors.start_date"></span>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" x-model="form.end_date" value="{{ old('end_date', $campaign->end_date) }}">
                    <span class="form-error" x-show="errors.end_date" x-text="errors.end_date"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" x-model="form.status" required>
                        <option value="active" {{ $campaign->status == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="paused" {{ $campaign->status == 'paused' ? 'selected' : '' }}>Paused</option>
                        <option value="completed" {{ $campaign->status == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                    <span class="form-error" x-show="errors.status" x-text="errors.status"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Update Campaign</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('campaigns.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                name: '{{ old('name', $campaign->name) }}',
                description: '{{ old('description', $campaign->description) }}',
                start_date: '{{ old('start_date', $campaign->start_date) }}',
                end_date: '{{ old('end_date', $campaign->end_date) }}',
                status: '{{ old('status', $campaign->status) }}',
                budget: '{{ old('budget', $campaign->budget) }}',
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
                        window.location.href = data.redirect || '{{ route("campaigns.index") }}';
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
