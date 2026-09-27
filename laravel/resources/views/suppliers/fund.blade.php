@extends('roles.layout')

@section('title', 'Supplier Fund')

@section('content')
<div x-data="supplierFundManager()">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Supplier Fund Transactions</h2>
            <button type="button" class="btn btn-primary" @click="openModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Fund
            </button>
        </div>

        @if(session('success'))
        <div x-data="flashMessage()" x-show="show" x-transition x-cloak class="alert alert-success show" style="margin: 16px 16px 0;">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div x-data="flashMessage()" x-show="show" x-transition x-cloak class="alert alert-error show" style="margin: 16px 16px 0;">
            {{ session('error') }}
        </div>
        @endif

        <!-- Filters -->
        <div style="padding: 16px;">
            <form method="GET" action="{{ route('suppliers.fund.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <select name="supplier_id" class="form-input" style="max-width: 250px;">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }} — {{ $currencySymbol }}{{ number_format($supplier->balance, 2) }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input" style="max-width: 180px;">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input" style="max-width: 180px;">
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['supplier_id', 'date_from', 'date_to']))
                    <a href="{{ route('suppliers.fund.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Supplier</th>
                    <th>Description</th>
                    <th>In Amount</th>
                    <th>Out Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($funds as $fund)
                    <tr>
                        <td>#{{ $fund->id }}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; flex-shrink: 0;">
                                    {{ $fund->supplier ? substr($fund->supplier->name, 0, 1) : '?' }}
                                </div>
                                <div>
                                    <strong>{{ $fund->supplier->name ?? 'N/A' }}</strong>
                                    <div style="font-size: 12px; color: var(--text-muted);">{{ $fund->supplier->email ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $fund->description ?? '—' }}</td>
                        <td style="color: var(--success); font-weight: 500;">
                            @if($fund->in_amount > 0)
                                {{ $currencySymbol }}{{ number_format($fund->in_amount, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="color: var(--error); font-weight: 500;">
                            @if($fund->out_amount > 0)
                                {{ $currencySymbol }}{{ number_format($fund->out_amount, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $fund->created_at ? $fund->created_at->format('M d, Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No fund transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($funds->hasPages())
        <div style="padding: 16px;">
            {{ $funds->links() }}
        </div>
        @endif
    </div>

    <!-- Add Fund Modal -->
    <div x-show="showModal" x-cloak :class="{ 'show': showModal }" class="modal-overlay" @click.self="closeModal()">
        <div class="modal" @click.stop style="max-width: 480px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 18px; font-weight: 600;">Add Fund to Supplier</h3>
                <button type="button" @click="closeModal()" style="background: none; border: none; cursor: pointer; color: var(--text-muted);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('suppliers.fund.store') }}">
                @csrf
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 14px;">Supplier *</label>
                    <select name="supplier_id" class="form-input" style="width: 100%;" required x-model="selectedSupplier" @change="fetchBalance()">
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }} — {{ $supplier->email }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="selectedSupplier" style="margin-bottom: 16px; padding: 12px; background: var(--bg-gray); border-radius: 8px;">
                    <div style="font-size: 13px; color: var(--text-muted);">Current Balance</div>
                    <div style="font-size: 20px; font-weight: 600;" :style="{ color: currentBalance >= 0 ? 'var(--success)' : 'var(--error)' }">
                        {{ $currencySymbol }}<span x-text="Number(currentBalance).toFixed(2)"></span>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 14px;">Amount *</label>
                    <input type="number" name="amount" class="form-input" style="width: 100%;" required min="0.01" step="0.01" placeholder="0.00" x-model="amount">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 14px;">Description</label>
                    <input type="text" name="description" class="form-input" style="width: 100%;" placeholder="Optional note" maxlength="500">
                </div>

                <div x-show="amount > 0 && selectedSupplier" style="margin-bottom: 16px; padding: 12px; background: var(--bg-gray); border-radius: 8px;">
                    <div style="font-size: 13px; color: var(--text-muted);">New Balance After Fund</div>
                    <div style="font-size: 20px; font-weight: 600; color: var(--success);">
                        {{ $currencySymbol }}<span x-text="newBalance"></span>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" @click="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="!selectedSupplier || !amount">
                        Add Fund
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
