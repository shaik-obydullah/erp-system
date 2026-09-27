@extends('roles.layout')

@section('title', 'Stock Adjustments')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Stock Adjustments</h2>
        <a href="{{ route('stock-adjustments.create') }}" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Adjustment
        </a>
    </div>

        <div style="padding: 16px;">
            <form method="GET" action="{{ route('stock-adjustments.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by product name..." style="flex: 1; min-width: 200px;">
                <select name="reason" class="form-input" style="max-width: 180px;">
                    <option value="">All Reasons</option>
                    <option value="correction" {{ request('reason') === 'correction' ? 'selected' : '' }}>Correction</option>
                    <option value="damage" {{ request('reason') === 'damage' ? 'selected' : '' }}>Damage</option>
                    <option value="return" {{ request('reason') === 'return' ? 'selected' : '' }}>Return</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'reason']))
                    <a href="{{ route('stock-adjustments.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Batch/Lot</th>
                    <th>Qty Adjusted</th>
                    <th>Reason</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($adjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustment->id }}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; flex-shrink: 0;">
                                    {{ substr($adjustment->stock->product->name ?? 'N', 0, 1) }}
                                </div>
                                <strong>{{ $adjustment->stock->product->name ?? '—' }}</strong>
                            </div>
                        </td>
                        <td>
                            @if($adjustment->batch || $adjustment->lot)
                                {{ $adjustment->batch ?? '—' }} / {{ $adjustment->lot ?? '—' }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="font-weight: 600;">{{ $adjustment->quantity }}</td>
                        <td>
                            <span class="badge {{ $adjustment->reason === 'damage' ? 'badge-red' : ($adjustment->reason === 'return' ? 'badge-orange' : 'badge-green') }}">
                                {{ ucfirst($adjustment->reason) }}
                            </span>
                        </td>
                        <td>{{ $adjustment->created_at ? $adjustment->created_at->format('M d, Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No stock adjustments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($adjustments->hasPages())
        <div style="padding: 16px;">
            {{ $adjustments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
