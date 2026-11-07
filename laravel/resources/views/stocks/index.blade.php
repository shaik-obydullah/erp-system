@extends('roles.layout')

@section('title', 'Stocks')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Stocks</h2>
            <a href="{{ route('stocks.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Stock
            </a>
        </div>

        <div style="padding: 16px;">
            <form method="GET" action="{{ route('stocks.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by product name or SKU..." style="flex: 1; min-width: 200px;">
                <select name="fk_product_id" class="form-input" style="max-width: 200px;">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('fk_product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-input" style="max-width: 180px;">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="archive" {{ request('status') === 'archive' ? 'selected' : '' }}>Archive</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'status', 'fk_product_id']))
                    <a href="{{ route('stocks.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Batch/Lot</th>
                    <th>Qty</th>
                    <th>Buy Price</th>
                    <th>Sale Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                    <tr id="row-{{ $stock->id }}">
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; flex-shrink: 0;">
                                    {{ substr($stock->product->name ?? 'N', 0, 1) }}
                                </div>
                                <div>
                                    <strong>{{ $stock->product->name ?? '—' }}</strong>
                                    @if($stock->product?->sku)
                                        <div style="font-size: 12px; color: var(--text-secondary);">{{ $stock->product->sku }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($stock->batch || $stock->lot)
                                <span style="font-size: 12px;">{{ $stock->batch ?? '—' }} / {{ $stock->lot ?? '—' }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span style="color: {{ $stock->quantity > 0 ? 'var(--success, #22c55e)' : 'var(--error)' }}; font-weight: 600;">
                                {{ $stock->quantity }}
                            </span>
                        </td>
                        <td>{{ $currencySymbol }}{{ number_format($stock->buy_price, 2) }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($stock->sale_price, 2) }}</td>
                        <td>
                            <span class="badge {{ $stock->status === 'active' ? 'badge-green' : ($stock->status === 'inactive' ? 'badge-orange' : '') }}">
                                {{ ucfirst($stock->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('stocks.edit', $stock) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">No stocks found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($stocks->hasPages())
        <div style="padding: 16px;">
            {{ $stocks->links() }}
        </div>
        @endif
    </div>

@endsection
