@extends('storefront.layout')
@section('title', $vendor->name . ' — Vendor Store')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Vendor Header -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8 mb-8">
        <div class="flex flex-col md:flex-row items-start gap-6">
            <div class="w-20 h-20 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                <span class="text-3xl font-bold text-primary-700">{{ substr($vendor->name, 0, 1) }}</span>
            </div>
            <div class="flex-1">
                <h1 class="text-3xl font-bold mb-2">{{ $vendor->name }}</h1>
                @if($vendor->address)
                <p class="text-gray-500 text-sm flex items-center gap-1 mb-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ $vendor->address }}
                </p>
                @endif
                @if($vendor->mobile)
                <p class="text-gray-500 text-sm flex items-center gap-1 mb-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ $vendor->mobile }}
                </p>
                @endif
                @if($vendor->email)
                <p class="text-gray-500 text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    {{ $vendor->email }}
                </p>
                @endif
            </div>
            <div class="flex items-center gap-2 text-sm">
                <div class="flex text-accent-500">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                </div>
                <span class="font-medium">Verified Vendor</span>
            </div>
        </div>
    </div>

    <!-- Products -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold">Products by {{ $vendor->name }}</h2>
        <span class="text-sm text-gray-500">{{ $products->total() }} products</span>
    </div>

    @if($products->count())
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
        @foreach($products as $product)
        @php $stock = $product->stocks->first(); @endphp
        <a href="{{ route('store.product', $product->url_slug) }}"
           class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center p-6 relative">
                <div class="text-5xl opacity-20 group-hover:opacity-30 transition">📦</div>
                @if($product->brand)
                <span class="absolute top-2 right-2 bg-gray-900/70 text-white text-xs px-2 py-0.5 rounded-full">{{ $product->brand->name }}</span>
                @endif
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-sm line-clamp-2 group-hover:text-primary-600 transition mb-1">{{ $product->name }}</h3>
                @if($stock)
                <span class="text-lg font-bold text-primary-600">{{ $currencySymbol }}{{ number_format($stock->sale_price, 2) }}</span>
                @endif
            </div>
        </a>
        @endforeach
    </div>

    @if($products->hasPages())
    <div class="mt-8 flex justify-center">
        {{ $products->links('pagination::tailwind') }}
    </div>
    @endif

    @else
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <div class="text-5xl mb-4">📦</div>
        <h3 class="text-lg font-semibold mb-2">No products yet</h3>
        <p class="text-sm text-gray-500">This vendor hasn't listed any products yet.</p>
    </div>
    @endif
</div>
@endsection
