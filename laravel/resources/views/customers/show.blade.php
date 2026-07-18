@extends('roles.layout')

@section('title', 'Customer Details')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Customer: {{ $customer->name }}</h2>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">Back to Customers</a>
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; display: block;">Full Name</label>
                    <div style="font-size: 16px; font-weight: 600;">{{ $customer->name }}</div>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; display: block;">Email</label>
                    <div style="font-size: 16px;">{{ $customer->email }}</div>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; display: block;">Phone</label>
                    <div style="font-size: 16px;">{{ $customer->phone ?? '—' }}</div>
                </div>
            </div>
            <div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; display: block;">Status</label>
                    <span style="padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600; background: {{ $customer->status === 'active' ? '#dcfce7' : '#fee2e2' }}; color: {{ $customer->status === 'active' ? '#16a34a' : '#dc2626' }};">
                        {{ ucfirst($customer->status) }}
                    </span>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; display: block;">Balance</label>
                    <div style="font-size: 20px; font-weight: 700; color: {{ $customer->balance >= 0 ? 'var(--success, #22c55e)' : 'var(--error)' }};">{{ $currencySymbol }}{{ number_format($customer->balance ?? 0, 2) }}</div>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; display: block;">Address</label>
                    <div style="font-size: 16px;">{{ $customer->address ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
