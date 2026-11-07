@extends('roles.layout')

@section('title', 'Fixed Assets')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Fixed Assets</h2>
            <a href="{{ route('fixed-assets.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Asset
            </a>
        </div>

        <div style="padding: 16px;">
            <form method="GET" action="{{ route('fixed-assets.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search assets..." style="flex: 1; min-width: 200px;">
                <button type="submit" class="btn btn-primary">Search</button>
                @if(request()->has('search'))
                    <a href="{{ route('fixed-assets.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fixedAssets as $asset)
                    <tr id="row-{{ $asset->id }}">
                        <td>{{ $asset->id }}</td>
                        <td><strong>{{ $asset->name }}</strong></td>
                        <td>{{ $currencySymbol }}{{ number_format($asset->price, 2) }}</td>
                        <td>{{ $asset->description ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('fixed-assets.edit', $asset) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">No fixed assets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($fixedAssets->hasPages())
        <div style="padding: 16px;">
            {{ $fixedAssets->links() }}
        </div>
        @endif
    </div>

@endsection
