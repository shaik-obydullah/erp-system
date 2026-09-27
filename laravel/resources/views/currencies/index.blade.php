@extends('roles.layout')

@section('title', 'Currencies')

@section('content')
<div x-data="deleteHandler()" x-init="init()">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Currencies</h2>
            <a href="{{ route('currencies.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Currency
            </a>
        </div>

        <div x-show="successMessage" x-cloak class="alert alert-success show" style="margin: 16px 16px 0;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span x-text="successMessage"></span>
        </div>

        <!-- Filters -->
        <div style="padding: 16px;">
            <form method="GET" action="{{ route('currencies.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by code or name..." style="flex: 1; min-width: 200px;">
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->has('search'))
                    <a href="{{ route('currencies.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Symbol</th>
                    <th>Exchange Rate</th>
                    <th>Base?</th>
                    <th>Active?</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($currencies as $currency)
                    <tr id="row-{{ $currency->id }}">
                        <td><strong>{{ $currency->code }}</strong></td>
                        <td>{{ $currency->name }}</td>
                        <td>{{ $currency->symbol }}</td>
                        <td>{{ number_format($currency->exchange_rate, 6) }}</td>
                        <td>
                            @if($currency->is_base)
                                <span class="badge badge-green">Base</span>
                            @else
                                <form method="POST" action="{{ route('currencies.set-base', $currency) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm">Set as Base</button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $currency->is_active ? 'badge-green' : 'badge-orange' }}">
                                {{ $currency->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('currencies.edit', $currency) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                                @unless($currency->is_base)
                                <button type="button" class="btn btn-ghost btn-sm" style="color: var(--error);" @click="confirmDelete({{ $currency->id }}, '{{ route('currencies.destroy', $currency) }}', '{{ e($currency->code) }}')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    Delete
                                </button>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">No currencies found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($currencies->hasPages())
        <div style="padding: 16px;">
            {{ $currencies->links() }}
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
                    <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Delete Currency</h3>
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
</div>
@endsection
