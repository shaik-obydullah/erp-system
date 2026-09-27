@extends('roles.layout')

@section('title', 'Suppliers')

@section('content')
<div x-data="deleteHandler()" x-init="init()">
<div x-data="{ showImportModal: false, closeImportModal() { this.showImportModal = false; } }">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Suppliers</h2>
            <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Supplier
            </a>
        </div>

        <div x-show="successMessage" x-cloak class="alert alert-success show" style="margin: 16px 16px 0;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span x-text="successMessage"></span>
        </div>

        <!-- Filters & Actions -->
        <div style="padding: 16px;">
            <form method="GET" action="{{ route('suppliers.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by name, email, or mobile..." style="flex: 1; min-width: 180px;">
                <select name="status" class="form-input" style="max-width: 160px;">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('suppliers.index') }}" class="btn btn-ghost">Clear</a>
                @endif

                <div style="margin-left: auto; display: flex; gap: 8px;">
                    <a href="{{ route('suppliers.export') }}" class="btn btn-secondary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Export
                    </a>
                    <button type="button" class="btn btn-secondary" @click="showImportModal = true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Import
                    </button>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr id="row-{{ $supplier->id }}">
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; flex-shrink: 0;">
                                    {{ substr($supplier->name, 0, 1) }}
                                </div>
                                <div>
                                    <strong>{{ $supplier->name }}</strong>
                                    @if($supplier->address)
                                        <div style="font-size: 12px; color: var(--text-secondary);">{{ Str::limit($supplier->address, 40) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $supplier->email }}</td>
                        <td>{{ $supplier->mobile ?? '—' }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($supplier->balance, 2) }}</td>
                        <td>
                            <span class="badge {{ $supplier->status === 'active' ? 'badge-green' : 'badge-orange' }}">
                                {{ ucfirst($supplier->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                                <button type="button" class="btn btn-ghost btn-sm" style="color: var(--error);" @click="confirmDelete({{ $supplier->id }}, '{{ route('suppliers.destroy', $supplier) }}', '{{ e($supplier->name) }}')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No suppliers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($suppliers->hasPages())
        <div style="padding: 16px;">
            {{ $suppliers->links() }}
        </div>
        @endif
    </div>

    <!-- Delete Confirm Modal -->
    <div x-show="showModal" x-cloak :class="{ 'show': showModal }" class="modal-overlay" @click.self="cancelDelete()">
        <div class="modal" @click.stop>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--error-bg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--error)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Delete Supplier</h3>
                </div>
            </div>
            <p style="margin: 0 0 24px; font-size: 14px; color: var(--text-secondary); line-height: 1.5;">
                Are you sure you want to delete <strong x-text="deleteLabel"></strong>? This action cannot be undone.
            </p>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" @click="cancelDelete()">Cancel</button>
                <button type="button" class="btn btn-danger" @click="executeDelete()">Delete</button>
            </div>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div x-show="showImportModal" x-cloak :class="{ 'show': showImportModal }" class="modal-overlay" @click.self="closeImportModal()">
        <div class="modal" @click.stop>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-bg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Import Suppliers</h3>
                    <p style="margin: 2px 0 0; font-size: 13px; color: var(--text-secondary);">Upload a CSV file with columns: Name, Email, Mobile, Address, Balance, Status</p>
                </div>
            </div>
            <form action="{{ route('suppliers.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom: 20px;">
                    <label for="csv_file" style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px;">CSV File</label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required
                        style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; background: var(--bg); color: var(--text);">
                    <p style="margin: 6px 0 0; font-size: 12px; color: var(--text-secondary);">Max file size: 5MB. Accepted formats: .csv, .txt</p>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" @click="closeImportModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
