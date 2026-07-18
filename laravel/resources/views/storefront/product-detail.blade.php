@extends('storefront.layout')
@section('title', $product->name)

@section('head')
<style>
    .zoom-container {
        position: relative;
        overflow: hidden;
        cursor: crosshair;
    }
    .zoom-container .zoom-lens {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 32px;
        height: 32px;
        background: rgba(255,255,255,0.9);
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        opacity: 0.7;
        transition: opacity 0.2s;
        z-index: 10;
    }
    .zoom-container:hover .zoom-lens {
        opacity: 0;
    }
    .zoom-target {
        transition: transform 0.3s ease;
        transform-origin: var(--zoom-x, center) var(--zoom-y, center);
    }
    .zoom-container:hover .zoom-target {
        transform: scale(2);
    }
    .wishlist-btn.active svg {
        fill: currentColor;
        color: #ef4444;
    }
    .tab-active {
        border-bottom: 2px solid #2563eb;
        color: #2563eb;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8"
     x-data="{
         zoomX: '50%',
         zoomY: '50%',
         activeTab: 'description',
         qty: 1,
         wishlisted: false,
         copied: false,
         relatedQty: {}
     }"
     x-init="wishlisted = JSON.parse(localStorage.getItem('wishlist') || '[]').some(i => i.id == '{{ $product->id }}')">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6 flex-wrap">
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
        <!-- Image with Zoom -->
        <div class="zoom-container bg-white rounded-2xl border border-gray-200 p-8 flex items-center justify-center aspect-square"
             @mousemove="const r = $el.getBoundingClientRect(); zoomX = ((event.clientX - r.left) / r.width * 100) + '%'; zoomY = ((event.clientY - r.top) / r.height * 100) + '%'"
             :style="'--zoom-x:' + zoomX + ';--zoom-y:' + zoomY">
            <div class="zoom-lens">
                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
            </div>
            <div class="zoom-target text-[120px] opacity-15 select-none">
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
            <div class="flex items-baseline gap-3 mb-4">
                <span class="text-4xl font-bold text-primary-600">{{ $currencySymbol }}{{ number_format($stock->sale_price, 2) }}</span>
                @if($stock->buy_price && $stock->buy_price > $stock->sale_price)
                <span class="text-lg text-gray-400 line-through">{{ $currencySymbol }}{{ number_format($stock->buy_price, 2) }}</span>
                @endif
            </div>
            @endif

            <!-- Delivery Badge -->
            <div class="inline-flex items-center gap-2 bg-green-50 text-green-700 text-xs font-medium px-3 py-1.5 rounded-full mb-5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
                Free shipping on orders over $50
            </div>

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

            <!-- Quantity + Add to Cart -->
            @if($stock && $stock->quantity > 0)
            <div class="flex items-center gap-3 mb-4">
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

            <!-- Wishlist + Share Buttons -->
            <div class="flex items-center gap-3 mt-2">
                <button @click="
                    let wl = JSON.parse(localStorage.getItem('wishlist') || '[]');
                    if (wishlisted) { wl = wl.filter(i => i.id != '{{ $product->id }}'); } 
                    else { wl.push({ id: '{{ $product->id }}', name: '{{ e($product->name) }}', price: '{{ $stock->sale_price ?? 0 }}' }); }
                    localStorage.setItem('wishlist', JSON.stringify(wl));
                    wishlisted = !wishlisted;
                " :class="wishlisted ? 'border-red-200 bg-red-50 text-red-600' : 'border-gray-200 bg-white text-gray-600'"
                   class="flex items-center gap-2 px-4 py-2 border rounded-lg text-sm font-medium hover:shadow transition">
                    <svg class="w-4 h-4" :fill="wishlisted ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                    <span x-text="wishlisted ? 'Wishlisted' : 'Add to Wishlist'"></span>
                </button>

                <button @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)"
                    class="flex items-center gap-2 px-4 py-2 border border-gray-200 bg-white text-gray-600 rounded-lg text-sm font-medium hover:shadow transition">
                    <svg x-show="!copied" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/><path d="M10.172 13.828a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    <svg x-show="copied" x-cloak class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
                    <span x-text="copied ? 'Copied!' : 'Copy Link'"></span>
                </button>

                <button class="flex items-center gap-2 px-4 py-2 border border-gray-200 bg-white text-gray-600 rounded-lg text-sm font-medium hover:shadow transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                    Share
                </button>
            </div>
        </div>
    </div>

    <!-- Product Tabs -->
    <div class="mt-14 bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="flex border-b px-6">
            <button @click="activeTab = 'description'" :class="activeTab === 'description' ? 'tab-active' : 'text-gray-500 hover:text-gray-700'" class="px-5 py-4 text-sm transition">Description</button>
            <button @click="activeTab = 'specs'" :class="activeTab === 'specs' ? 'tab-active' : 'text-gray-500 hover:text-gray-700'" class="px-5 py-4 text-sm transition">Specifications</button>
            <button @click="activeTab = 'reviews'" :class="activeTab === 'reviews' ? 'tab-active' : 'text-gray-500 hover:text-gray-700'" class="px-5 py-4 text-sm transition">Reviews ({{ $product->review_number ?? 0 }})</button>
        </div>

        <!-- Description Tab -->
        <div x-show="activeTab === 'description'" x-transition:enter class="p-6">
            @if($product->description)
            <p class="text-gray-600 leading-relaxed">{{ $product->description }}</p>
            @else
            <p class="text-gray-400 text-sm italic">No description available for this product.</p>
            @endif
        </div>

        <!-- Specifications Tab -->
        <div x-show="activeTab === 'specs'" x-transition:enter class="p-6">
            <table class="w-full text-sm">
                <tbody>
                    @if($product->category)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 pr-6 text-gray-500 font-medium w-44">Category</td>
                        <td class="py-3">{{ $product->category->name }}</td>
                    </tr>
                    @endif
                    @if($product->category && $product->category->parent)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 pr-6 text-gray-500 font-medium">Parent Category</td>
                        <td class="py-3">{{ $product->category->parent->name }}</td>
                    </tr>
                    @endif
                    @if($product->brand)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 pr-6 text-gray-500 font-medium">Brand</td>
                        <td class="py-3">{{ $product->brand->name }}</td>
                    </tr>
                    @endif
                    @if($product->supplier)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 pr-6 text-gray-500 font-medium">Vendor</td>
                        <td class="py-3"><a href="{{ route('store.vendor', $product->supplier->name) }}" class="text-primary-600 hover:underline">{{ $product->supplier->name }}</a></td>
                    </tr>
                    @endif
                    @if($product->unit)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 pr-6 text-gray-500 font-medium">Unit</td>
                        <td class="py-3">{{ $product->unit->name }}</td>
                    </tr>
                    @endif
                    @if($stock)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 pr-6 text-gray-500 font-medium">Availability</td>
                        <td class="py-3">
                            @if($stock->quantity > 0)
                                <span class="text-green-600 font-medium">In Stock ({{ $stock->quantity }} units)</span>
                            @else
                                <span class="text-red-600 font-medium">Out of Stock</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="py-3 pr-6 text-gray-500 font-medium">SKU</td>
                        <td class="py-3 font-mono text-xs">{{ $product->id }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Reviews Tab -->
        <div x-show="activeTab === 'reviews'" x-transition:enter class="p-6">
            @if(isset($product->reviews) && $product->reviews->count())
            <div class="space-y-4">
                @foreach($product->reviews as $review)
                <div class="border-b border-gray-100 pb-4 last:border-0">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="flex text-accent-500">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= ($review->rating ?? 5))
                                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                @else
                                    <svg class="w-3.5 h-3.5 fill-current text-gray-300" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                @endif
                            @endfor
                        </div>
                        <span class="text-xs text-gray-400">{{ $review->created_at?->diffForHumans() ?? '' }}</span>
                    </div>
                    <p class="text-gray-600 text-sm">{{ $review->comment ?? $review->text ?? '' }}</p>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-10">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <p class="text-gray-400 text-sm">No reviews yet. Be the first to review this product!</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Related Products -->
    @if(isset($relatedProducts) && $relatedProducts->count())
    <section class="mt-14">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">Related Products</h2>
            @if($product->category)
            <a href="{{ route('store.products', ['category' => $product->category_id]) }}" class="text-sm text-primary-600 hover:underline font-medium">View More from {{ $product->category->name }} →</a>
            @endif
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            @foreach($relatedProducts as $rp)
            @php $rpStock = $rp->stocks->first(); @endphp
            <div class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col">
                <a href="{{ route('store.product', $rp->url_slug) }}" class="block">
                    <div class="aspect-square bg-gray-50 flex items-center justify-center p-6">
                        <div class="text-4xl opacity-20">
                            @if($rp->category)
                                @switch($rp->category->name)
                                    @case('Phones') 📱 @break
                                    @case('Laptops') 💻 @break
                                    @case('Audio') 🎧 @break
                                    @case('Shoes') 👟 @break
                                    @default 📦 @endswitch
                            @else 📦 @endif
                        </div>
                    </div>
                </a>
                <div class="p-4 flex flex-col flex-1">
                    <a href="{{ route('store.product', $rp->url_slug) }}">
                        <h3 class="font-semibold text-sm line-clamp-2 group-hover:text-primary-600 transition mb-1">{{ $rp->name }}</h3>
                    </a>
                    @if($rp->supplier)
                    <p class="text-xs text-gray-400 mb-1">{{ $rp->supplier->name }}</p>
                    @endif
                    @if($rp->review_number > 0)
                    <div class="flex items-center gap-1 mb-2">
                        <div class="flex text-accent-500">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($rp->review_avg))
                                    <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                @else
                                    <svg class="w-3 h-3 fill-current text-gray-300" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                @endif
                            @endfor
                        </div>
                        <span class="text-[10px] text-gray-400">({{ $rp->review_number }})</span>
                    </div>
                    @endif
                    @if($rpStock)
                    <span class="text-lg font-bold text-primary-600 mt-auto mb-2">{{ $currencySymbol }}{{ number_format($rpStock->sale_price, 2) }}</span>
                    @endif
                    @if($rpStock && $rpStock->quantity > 0)
                    <button @click="
                        let rQty = relatedQty['{{ $rp->id }}'] || 1;
                        addToCartDetail('{{ $rp->id }}', '{{ e($rp->name) }}', '{{ $rpStock->sale_price }}', rQty);
                    "
                        class="w-full px-4 py-2 bg-primary-600 text-white text-xs font-semibold rounded-lg hover:bg-primary-700 transition flex items-center justify-center gap-1.5 mt-auto">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        Add to Cart
                    </button>
                    @endif
                </div>
            </div>
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
