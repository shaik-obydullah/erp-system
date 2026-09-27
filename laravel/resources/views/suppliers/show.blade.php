@extends('roles.layout')

@section('title', 'Supplier Details')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Supplier: {{ $supplier->name }}</h2>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Back to Suppliers</a>
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; display: block;">Company Name</label>
                    <div style="font-size: 16px; font-weight: 600;">{{ $supplier->name }}</div>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; display: block;">Email</label>
                    <div style="font-size: 16px;">{{ $supplier->email }}</div>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; display: block;">Mobile</label>
                    <div style="font-size: 16px;">{{ $supplier->mobile ?? '—' }}</div>
                </div>
            </div>
            <div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; display: block;">Status</label>
                    <span style="padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600; background: {{ $supplier->status === 'active' ? '#dcfce7' : '#fee2e2' }}; color: {{ $supplier->status === 'active' ? '#16a34a' : '#dc2626' }};">
                        {{ ucfirst($supplier->status) }}
                    </span>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; display: block;">Balance</label>
                    <div style="font-size: 20px; font-weight: 700; color: {{ $supplier->balance >= 0 ? 'var(--success, #22c55e)' : 'var(--error)' }};">{{ $currencySymbol }}{{ number_format($supplier->balance ?? 0, 2) }}</div>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; display: block;">Address</label>
                    <div style="font-size: 16px;">{{ $supplier->address ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
