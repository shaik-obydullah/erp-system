@extends('roles.layout')

@section('title', 'Production Plannings')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Production Plannings</h2>
            <a href="{{ route('production-plannings.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Planning
            </a>
        </div>

        <div style="padding: 16px;">
            <form method="GET" action="{{ route('production-plannings.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by BOM name..." style="flex: 1; min-width: 200px;">
                <select name="finalized" class="form-input" style="max-width: 180px;">
                    <option value="">All Status</option>
                    <option value="yes" {{ request('finalized') === 'yes' ? 'selected' : '' }}>Finalized</option>
                    <option value="no" {{ request('finalized') === 'no' ? 'selected' : '' }}>Not Finalized</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'finalized']))
                    <a href="{{ route('production-plannings.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>BOM Name</th>
                    <th>Production Cost</th>
                    <th>Other Cost</th>
                    <th>Expected Profit</th>
                    <th>Quantity</th>
                    <th>Finalized</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plannings as $planning)
                    <tr id="row-{{ $planning->id }}">
                        <td><strong>{{ $planning->billOfMaterial->name ?? '—' }}</strong></td>
                        <td>{{ $currencySymbol }}{{ number_format($planning->production_cost, 2) }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($planning->other_cost, 2) }}</td>
                        <td style="color: var(--success, #22c55e);">{{ $currencySymbol }}{{ number_format($planning->expected_profit, 2) }}</td>
                        <td>{{ $planning->quantity }}</td>
                        <td>
                            <span class="badge {{ $planning->finalized === 'yes' ? 'badge-green' : 'badge-orange' }}">
                                {{ $planning->finalized === 'yes' ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('production-plannings.edit', $planning) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">No production plannings found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($plannings->hasPages())
        <div style="padding: 16px;">
            {{ $plannings->links() }}
        </div>
        @endif
    </div>
@endsection
