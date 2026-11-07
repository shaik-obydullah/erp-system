@extends('roles.layout')

@section('title', 'Campaigns')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Campaigns</h2>
            <a href="{{ route('campaigns.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Campaign
            </a>
        </div>

        <div style="padding: 16px;">
            <form method="GET" action="{{ route('campaigns.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search campaigns..." style="flex: 1; min-width: 200px;">
                <select name="status" class="form-input" style="max-width: 180px;">
                    <option value="">All Status</option>
                    <option value="planned" {{ request('status') === 'planned' ? 'selected' : '' }}>Planned</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="canceled" {{ request('status') === 'canceled' ? 'selected' : '' }}>Canceled</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('campaigns.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Budget</th>
                    <th>Actually Spent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    <tr id="row-{{ $campaign->id }}">
                        <td><strong>{{ $campaign->name }}</strong></td>
                        <td>{{ $campaign->start_date ? \Carbon\Carbon::parse($campaign->start_date)->format('d M Y') : '—' }}</td>
                        <td>{{ $campaign->end_date ? \Carbon\Carbon::parse($campaign->end_date)->format('d M Y') : '—' }}</td>
                        <td>
                            <span class="badge {{ $campaign->status === 'active' ? 'badge-green' : ($campaign->status === 'planned' ? 'badge-orange' : 'badge-red') }}">
                                {{ ucfirst($campaign->status) }}
                            </span>
                        </td>
                        <td>{{ $currencySymbol }}{{ number_format($campaign->budget, 2) }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($campaign->spent_amount, 2) }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('campaigns.edit', $campaign) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">No campaigns found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <p style="padding: 0 16px 16px; margin: 0; font-size: 13px; color: var(--text-secondary);">
            Budget is your plan. "Actually Spent" is the real money that goes into accounting.
        </p>

        @if($campaigns->hasPages())
        <div style="padding: 16px;">
            {{ $campaigns->links() }}
        </div>
        @endif
    </div>
@endsection
