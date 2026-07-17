@extends('roles.layout')

@section('title', 'Productions')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Productions</h2>
        <a href="{{ route('productions.create') }}" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Production
        </a>
    </div>

        <table>
            <thead>
                <tr>
                    <th>BOM Name</th>
                    <th>Production Cost</th>
                    <th>Other Cost</th>
                    <th>Expected Profit</th>
                    <th>Quantity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productions as $production)
                    <tr>
                        <td><strong>{{ $production->bom->name ?? '—' }}</strong></td>
                        <td>{{ $currencySymbol }}{{ number_format($production->production_cost, 2) }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($production->other_cost, 2) }}</td>
                        <td style="color: var(--success, #22c55e);">{{ $currencySymbol }}{{ number_format($production->expected_profit, 2) }}</td>
                        <td>{{ $production->quantity }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('productions.show', $production) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View
                                </a>
                                <a href="{{ route('productions.edit', $production) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No productions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($productions->hasPages())
        <div style="padding: 16px;">
            {{ $productions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
