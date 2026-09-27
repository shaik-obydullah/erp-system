@extends('roles.layout')

@section('title', 'Product Barcodes')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Product Barcodes</h2>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print Barcodes
        </button>
    </div>

    <div style="padding: 16px;">
        <form method="GET" action="{{ route('products.barcodes') }}" style="display: flex; gap: 12px; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by name, SKU, or barcode..." style="flex: 1; min-width: 200px;">
            <button type="submit" class="btn btn-primary">Search</button>
            @if(request()->has('search'))
                <a href="{{ route('products.barcodes') }}" class="btn btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    @if($products->count())
        <div style="padding: 0 16px 16px; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
            @foreach($products as $product)
                <div class="barcode-card" style="border: 1px solid var(--border); border-radius: 8px; padding: 16px; text-align: center; background: var(--bg-primary, #fff);">
                    <div style="font-weight: 600; margin-bottom: 4px;">{{ $product->name }}</div>
                    @if($product->sku)
                        <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px;">SKU: {{ $product->sku }}</div>
                    @endif
                    <svg class="barcode-svg" data-value="{{ $product->barcode }}"></svg>
                </div>
            @endforeach
        </div>

        @if($products->hasPages())
        <div style="padding: 16px;">
            {{ $products->links() }}
        </div>
        @endif
    @else
        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
            <p>No products with barcodes found.</p>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.barcode-svg').forEach(function (el) {
            JsBarcode(el, el.getAttribute('data-value'), {
                format: 'CODE128',
                width: 1.5,
                height: 40,
                displayValue: true
            });
        });
    });
</script>
<style>
    @media print {
        .sidebar, .card-header, form, .pagination, .alert { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .barcode-card { border: 1px solid #ccc !important; break-inside: avoid; }
    }
</style>
@endsection
