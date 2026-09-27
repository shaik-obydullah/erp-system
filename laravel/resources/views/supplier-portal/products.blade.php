@extends('supplier-portal.layout')

@section('title', 'My Products')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">My Products</h2>
    </div>

    <!-- Filters -->
    <div style="padding: 16px;">
        <form method="GET" action="{{ route('supplier-portal.products') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by name or SKU..." style="flex: 1; min-width: 200px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->has('search'))
                <a href="{{ route('supplier-portal.products') }}" class="btn btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td><strong>{{ $product->name }}</strong></td>
                    <td>{{ $product->sku ?? '-' }}</td>
                    <td>{{ $product->category->name ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $product->status === 'active' ? 'badge-green' : 'badge-orange' }}">
                            {{ ucfirst($product->status) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty-state">No products found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($products->hasPages())
    <div style="padding: 16px;">
        {{ $products->links() }}
    </div>
    @endif
</div>
@endsection
