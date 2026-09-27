@extends('roles.layout')

@section('title', 'Production Details')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Production #{{ $production->id }}</h2>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('productions.edit', $production) }}" class="btn btn-secondary">Edit</a>
            <a href="{{ route('productions.index') }}" class="btn btn-secondary">Back to Productions</a>
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <div>
                <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: var(--text-secondary);">Production Information</h3>
                <div style="display: grid; gap: 8px;">
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 140px;">BOM Name:</span> <strong>{{ $production->bom->name ?? '—' }}</strong></div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 140px;">Quantity:</span> {{ $production->quantity }}</div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 140px;">Created:</span> {{ $production->created_at->format('d M Y H:i') }}</div>
                </div>
            </div>
            <div>
                <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: var(--text-secondary);">Cost Details</h3>
                <div style="display: grid; gap: 8px;">
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 140px;">Production Cost:</span> <strong>{{ $currencySymbol }}{{ number_format($production->production_cost, 2) }}</strong></div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 140px;">Other Cost:</span> {{ $currencySymbol }}{{ number_format($production->other_cost, 2) }}</div>
                    <div style="display: flex; gap: 8px;"><span style="color: var(--text-secondary); min-width: 140px;">Expected Profit:</span> <strong style="color: var(--success, #22c55e);">{{ $currencySymbol }}{{ number_format($production->expected_profit, 2) }}</strong></div>
                    <div style="display: flex; gap: 8px; padding-top: 8px; border-top: 1px solid var(--border);"><span style="color: var(--text-secondary); min-width: 140px;">Total Cost:</span> <strong>{{ $currencySymbol }}{{ number_format($production->production_cost + $production->other_cost, 2) }}</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
