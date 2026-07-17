@extends('storefront.layout')
@section('title', $product->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('store.home') }}" class="hover:text-primary-600 transition">Home</a>
        <span>/</span>
        <a href="{{ route('store.products') }}" class="hover:text-primary-600 transition">Products</a>
        <span>/</span>
        @if($product->category)
        <a href="{{ route('store.products', ['category' => $product->category_id]) }}" class="hover:text-primary-600 transition">{{ $product->category->name }}</a>
        <span>/</span>
        @endif
        <span class="text-gray-900 truncate">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <!-- Image -->
        <div class="bg-white rounded-2xl border border-gray-200 p-8 flex items-center justify-center aspect-square">
            <div class="text-[120px] opacity-15">
                @if($product->category)
                    @switch($product->category->name)
                        @case('Phones') 📱 @break
                        @case('Laptops') 💻 @break
                        @case('Audio') 🎧 @break
                        @case('Shoes') 👟 @break
                        @default 📦 @endswitch
                @else 📦 @endif
            </div>
        </div>

        <!-- Details -->
        <div>
            @if($product->brand)
            <span class="inline-block px-3 py-1 bg-primary-50 text-primary-700 text-xs font-medium rounded-full mb-3">{{ $product->brand->name }}</span>
            @endif
            <h1 class="text-3xl font-bold mb-3">{{ $product->name }}</h1>

            @if($product->review_number > 0)
            <div class="flex items-center gap-2 mb-4">
                <div class="flex text-accent-500">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= floor($product->review_avg))
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                        @else
                            <svg class="w-4 h-4 fill-current text-gray-300" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                        @endif
                    @endfor
                </div>
                <span class="text-sm text-gray-500">({{ $product->review_number }} reviews)</span>
            </div>
            @endif

            @php $stock = $product->stocks->first(); @endphp
            @if($stock)
            <div class="flex items-baseline gap-3 mb-6">
                <span class="text-4xl font-bold text-primary-600">{{ $currencySymbol }}{{ number_format($stock->sale_price, 2) }}</span>
                @if($stock->buy_price && $stock->buy_price > $stock->sale_price)
                <span class="text-lg text-gray-400 line-through">{{ $currencySymbol }}{{ number_format($stock->buy_price, 2) }}</span>
                @endif
            </div>
            @endif

            <!-- Meta -->
            <div class="space-y-3 mb-6 text-sm">
                @if($product->category)
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 w-24">Category:</span>
                    <a href="{{ route('store.products', ['category' => $product->category_id]) }}" class="text-primary-600 hover:underline">{{ $product->category->name }}</a>
                </div>
                @endif
                @if($product->brand)
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 w-24">Brand:</span>
                    <span class="font-medium">{{ $product->brand->name }}</span>
                </div>
                @endif
                @if($product->supplier)
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 w-24">Vendor:</span>
                    <a href="{{ route('store.vendor', $product->supplier->name) }}" class="text-primary-600 hover:underline">{{ $product->supplier->name }}</a>
                </div>
                @endif
                @if($product->unit)
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 w-24">Unit:</span>
                    <span class="font-medium">{{ $product->unit->name }}</span>
                </div>
                @endif
                @if($stock)
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 w-24">Stock:</span>
                    @if($stock->quantity > 0)
                        <span class="text-green-600 font-medium">In Stock ({{ $stock->quantity }} available)</span>
                    @else
                        <span class="text-red-600 font-medium">Out of Stock</span>
                    @endif
                </div>
                @endif
            </div>

            <!-- Description -->
            @if($product->description)
            <div class="mb-6">
                <h3 class="font-semibold mb-2">Description</h3>
                <p class="text-gray-600 text-sm leading-relaxed">{{ $product->description }}</p>
            </div>
            @endif

            <!-- Add to Cart -->
            @if($stock && $stock->quantity > 0)
            <div class="flex items-center gap-4" x-data="{ qty: 1 }">
                <div class="flex items-center border border-gray-300 rounded-lg">
                    <button @click="qty = Math.max(1, qty - 1)" class="px-3 py-2 text-gray-600 hover:bg-gray-100 transition rounded-l-lg">−</button>
                    <span class="px-4 py-2 font-medium text-sm min-w-[3rem] text-center" x-text="qty"></span>
                    <button @click="qty++" class="px-3 py-2 text-gray-600 hover:bg-gray-100 transition rounded-r-lg">+</button>
                </div>
                <button @click="addToCartDetail('{{ $product->id }}', '{{ e($product->name) }}', '{{ $stock->sale_price }}', qty)"
                    class="flex-1 px-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition shadow-md flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                    Add to Cart
                </button>
            </div>
            @endif
        </div>
    </div>

    <!-- Related Products -->
    @if(isset($relatedProducts) && $relatedProducts->count())
    <section class="mt-16">
        <h2 class="text-2xl font-bold mb-6">Related Products</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            @foreach($relatedProducts as $rp)
            @php $rpStock = $rp->stocks->first(); @endphp
            <a href="{{ route('store.product', $rp->url_slug) }}"
               class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="aspect-square bg-gray-50 flex items-center justify-center p-6">
                    <div class="text-4xl opacity-20">📦</div>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-sm line-clamp-2 group-hover:text-primary-600 transition mb-1">{{ $rp->name }}</h3>
                    @if($rpStock)
                    <span class="text-lg font-bold text-primary-600">{{ $currencySymbol }}{{ number_format($rpStock->sale_price, 2) }}</span>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection

@section('scripts')
<script>
    function addToCartDetail(id, name, price, qty) {
        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
        let existing = cart.find(i => i.id == id);
        if (existing) { existing.qty += qty; }
        else { cart.push({ id, name, price: parseFloat(price), qty }); }
        localStorage.setItem('cart', JSON.stringify(cart));
        localStorage.setItem('cartCount', cart.reduce((s, i) => s + i.qty, 0));
        alert('Added to cart!');
        location.reload();
    }
</script>
@endsection
