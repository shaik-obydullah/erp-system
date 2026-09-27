@extends('roles.layout')

@section('title', 'Edit Shipping Zone')

@section('content')
<div class="card" x-data="formHandler('{{ route('shipping-zones.update', $shippingZone) }}', 'PUT')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Edit Zone: {{ $shippingZone->name }}</h2>
        <a href="{{ route('shipping-zones.index') }}" class="btn btn-secondary">Back to Zones</a>
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
                    <label for="name">Zone Name *</label>
                    <input type="text" id="name" x-model="form.name" required autofocus placeholder="e.g. United States, Europe">
                    <span class="form-error" x-show="errors.name" x-text="errors.name"></span>
                </div>
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" x-model="form.sort_order" placeholder="0">
                </div>
            </div>

            <div class="form-group" style="max-width: 300px;">
                <label for="status">Status *</label>
                <select id="status" x-model="form.status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label>Countries *</label>
                <p style="font-size: 12px; color: var(--text-secondary); margin: 0 0 8px;">
                    Select the countries this zone applies to.
                </p>
                <div style="max-height: 260px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; padding: 12px;">
                    <label class="permission-item" style="border-bottom: 1px solid var(--border); margin-bottom: 8px; padding-bottom: 8px;">
                        <input type="checkbox" x-model="form.rest_of_world" value="1">
                        <span><strong>Rest of World</strong> <span style="color: var(--text-secondary); font-size: 12px;">Applies to all other countries</span></span>
                    </label>
                    <template x-for="(name, code) in countries" :key="code">
                        <label class="permission-item">
                            <input type="checkbox" :value="code" x-model="form.countries">
                            <span x-text="name"></span>
                        </label>
                    </template>
                </div>
                <span class="form-error" x-show="errors.countries" x-text="errors.countries"></span>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Update Zone</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('shipping-zones.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Shipping Methods -->
<div x-data="methodForm()" x-init="initMethod()">
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h2 class="card-title">Shipping Methods</h2>
        <span class="badge {{ $shippingZone->methods->count() > 0 ? 'badge-green' : 'badge-gray' }}">{{ $shippingZone->methods->count() }} total</span>
    </div>
    <div class="card-body">
        @if($shippingZone->methods->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Cost</th>
                    <th>Minimum Order</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shippingZone->methods as $method)
                    <tr id="method-row-{{ $method->id }}">
                        <td><strong>{{ $method->name }}</strong></td>
                        <td>
                            <span class="badge badge-blue">{{ str_replace('_', ' ', ucwords($method->type, '_')) }}</span>
                        </td>
                        <td>${{ number_format($method->cost, 2) }}</td>
                        <td>{{ $method->min_order_amount !== null ? '$' . number_format($method->min_order_amount, 2) : '—' }}</td>
                        <td>
                            <span class="badge {{ $method->status === 'active' ? 'badge-green' : 'badge-orange' }}">
                                {{ ucfirst($method->status) }}
                            </span>
                        </td>
                        <td class="text-sm">{{ $method->sort_order }}</td>
                        <td>
                            <div class="actions">
                                <button type="button" class="btn btn-ghost btn-sm"
                                    @click="editMethod({{ $method->id }}, '{{ addslashes($method->name) }}', '{{ $method->type }}', {{ $method->cost }}, {{ $method->min_order_amount ?? 'null' }}, '{{ $method->status }}', {{ $method->sort_order }})">Edit</button>
                                <button type="button" class="btn btn-ghost btn-sm" style="color: var(--error);"
                                    @click="deleteMethod({{ $method->id }}, '{{ addslashes($method->name) }}')">Delete</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <p class="empty-state" style="padding: 24px; text-align: center; color: var(--text-secondary);">
                No shipping methods yet. Add a method below (e.g. Flat Rate, Free Shipping, Local Pickup).
            </p>
        @endif
    </div>
</div>

<!-- Add / Edit Method -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h2 class="card-title" x-text="methodForm.id ? 'Edit Shipping Method' : 'Add Shipping Method'"></h2>
    </div>
    <div class="card-body">
        <div x-show="methodError" x-cloak class="alert alert-error show">
            <span x-text="methodError"></span>
        </div>
        <form @submit.prevent="submitMethod($event)">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label for="m_name">Method Name *</label>
                    <input type="text" id="m_name" x-model="methodForm.name" required placeholder="e.g. Flat Rate, Express Delivery">
                </div>
                <div class="form-group" style="max-width: 260px;">
                    <label for="m_type">Type *</label>
                    <select id="m_type" x-model="methodForm.type" required @change="onTypeChange()">
                        <option value="flat_rate">Flat Rate</option>
                        <option value="free_shipping">Free Shipping</option>
                        <option value="local_pickup">Local Pickup</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="max-width: 220px;" x-show="methodForm.type === 'flat_rate'">
                    <label for="m_cost">Cost ($) *</label>
                    <input type="number" id="m_cost" x-model="methodForm.cost" step="0.01" min="0">
                </div>
                <div class="form-group" style="max-width: 240px;" x-show="methodForm.type === 'free_shipping'">
                    <label for="m_min">Free Above ($)</label>
                    <p style="font-size: 12px; color: var(--text-secondary); margin: 0 0 4px;">Free shipping requires order value at least this amount.</p>
                    <input type="number" id="m_min" x-model="methodForm.min_order_amount" step="0.01" min="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="max-width: 260px;">
                    <label for="m_status">Status</label>
                    <select id="m_status" x-model="methodForm.status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group" style="max-width: 160px;">
                    <label for="m_sort_order">Sort Order</label>
                    <input type="number" id="m_sort_order" x-model="methodForm.sort_order">
                </div>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="methodSubmitting">
                    <span x-show="!methodSubmitting" x-text="methodForm.id ? 'Update Method' : 'Add Method'"></span>
                    <span x-show="methodSubmitting">Saving...</span>
                </button>
                <button type="button" class="btn btn-secondary" x-show="methodForm.id" @click="resetMethodForm()">Cancel Edit</button>
            </div>
        </form>
    </div>
</div>
</div>

<script>
    const zoneId = {{ $shippingZone->id }};

    function methodForm() {
        return {
            methodForm: {
                id: null,
                name: '',
                type: 'flat_rate',
                cost: 0,
                min_order_amount: '',
                status: 'active',
                sort_order: 0,
            },
            methodError: '',
            methodSubmitting: false,
            csrfToken: '',
            initMethod() {
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            },
            onTypeChange() {
                if (this.methodForm.type !== 'free_shipping') {
                    if (this.methodForm.type === 'flat_rate') {
                        this.methodForm.min_order_amount = '';
                    }
                }
            },
            editMethod(id, name, type, cost, minAmount, status, sortOrder) {
                this.methodError = '';
                this.methodForm.id = id;
                this.methodForm.name = name;
                this.methodForm.type = type;
                this.methodForm.cost = cost;
                this.methodForm.min_order_amount = minAmount !== null ? minAmount : '';
                this.methodForm.status = status;
                this.methodForm.sort_order = sortOrder;
                window.scrollTo({ top: document.querySelector('.card:last-of-type').offsetTop - 90, behavior: 'smooth' });
            },
            resetMethodForm() {
                this.methodError = '';
                this.methodForm.id = null;
                this.methodForm.name = '';
                this.methodForm.type = 'flat_rate';
                this.methodForm.cost = 0;
                this.methodForm.min_order_amount = '';
                this.methodForm.status = 'active';
                this.methodForm.sort_order = 0;
            },
            async submitMethod(event) {
                this.methodError = '';
                this.methodSubmitting = true;
                try {
                    const isEdit = this.methodForm.id !== null;
                    const url = isEdit
                        ? `/shipping-zones/${zoneId}/methods/${this.methodForm.id}`
                        : `/shipping-zones/${zoneId}/methods`;
                    const response = await fetch(url, {
                        method: isEdit ? 'PUT' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.methodForm),
                    });
                    const data = await response.json();
                    if (response.ok) {
                        window.location.reload();
                        return;
                    }
                    this.methodError = data.message || 'Failed to save shipping method.';
                } catch (e) {
                    this.methodError = 'An unexpected error occurred.';
                } finally {
                    this.methodSubmitting = false;
                }
            },
            async deleteMethod(id, name) {
                if (!confirm('Delete shipping method "' + name + '"? This action cannot be undone.')) return;
                this.methodError = '';
                try {
                    const response = await fetch(`/shipping-zones/${zoneId}/methods/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await response.json();
                    if (response.ok) {
                        window.location.reload();
                        return;
                    }
                    this.methodError = data.message || 'Failed to delete shipping method.';
                } catch (e) {
                    this.methodError = 'An unexpected error occurred.';
                }
            },
        };
    }

    function formHandler(url, method) {
        return {
            form: {
                name: '{{ old('name', $shippingZone->name) }}',
                status: '{{ old('status', $shippingZone->status) }}',
                sort_order: {{ old('sort_order', $shippingZone->sort_order) }},
                countries: @json($shippingZone->country_list),
                rest_of_world: {{ in_array('*', $shippingZone->country_list, true) ? 'true' : 'false' }},
            },
            countries: @json($countries),
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
                        this.successMessage = data.message || 'Saved successfully.';
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