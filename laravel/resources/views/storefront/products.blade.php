@extends('storefront.layout')
@section('title', 'All Products')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold">All Products</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $products->total() }} products found</p>
        </div>
        <form action="{{ route('store.products') }}" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="search" value="{{ request('search') }}">
            <input type="hidden" name="category" value="{{ request('category') }}">
            <select name="sort" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none bg-white">
                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest</option>
                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low → High</option>
                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High → Low</option>
                <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name: A → Z</option>
                <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Top Rated</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 transition">Sort</button>
        </form>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filters -->
        <aside class="lg:w-56 flex-shrink-0">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="font-semibold text-sm mb-3">Categories</h3>
                <div class="space-y-1.5">
                    <a href="{{ route('store.products', array_filter(['search' => request('search'), 'sort' => request('sort')])) }}"
                       class="block text-sm px-3 py-1.5 rounded-lg {{ !request('category') ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                        All Categories
                    </a>
                    @foreach($categories as $cat)
                    <a href="{{ route('store.products', array_merge(request()->only('search', 'sort'), ['category' => $cat->id])) }}"
                       class="block text-sm px-3 py-1.5 rounded-lg {{ request('category') == $cat->id ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                        {{ $cat->name }}
                        <span class="text-gray-400 text-xs">({{ $cat->products_count }})</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </aside>

        <!-- Product Grid -->
        <div class="flex-1">
            @if($products->count())
            <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                @foreach($products as $product)
                @php $stock = $product->stocks->first(); @endphp
                <a href="{{ route('store.product', $product->url_slug) }}"
                   class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                    <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center p-6 relative">
                        <div class="text-5xl opacity-20 group-hover:opacity-30 transition">
                            @if($product->category)
                                @switch($product->category->name)
                                    @case('Phones') 📱 @break
                                    @case('Laptops') 💻 @break
                                    @case('Audio') 🎧 @break
                                    @case('Shoes') 👟 @break
                                    @default 📦 @endswitch
                            @else 📦 @endif
                        </div>
                        @if($product->brand)
                        <span class="absolute top-2 right-2 bg-gray-900/70 text-white text-xs px-2 py-0.5 rounded-full">{{ $product->brand->name }}</span>
                        @endif
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-sm line-clamp-2 group-hover:text-primary-600 transition mb-1">{{ $product->name }}</h3>
                        @if($product->supplier)
                        <p class="text-xs text-gray-400 mb-2">{{ $product->supplier->name }}</p>
                        @endif
                        @if($product->review_number > 0)
                        <div class="flex items-center gap-1 mb-2">
                            <div class="flex text-accent-500 text-xs">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= floor($product->review_avg))
                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                    @else
                                        <svg class="w-3 h-3 fill-current text-gray-300" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                    @endif
                                @endfor
                            </div>
                            <span class="text-xs text-gray-400">({{ $product->review_number }})</span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between mt-2">
                            @if($stock)
                            <span class="text-lg font-bold text-primary-600">{{ $currencySymbol }}{{ number_format($stock->sale_price, 2) }}</span>
                            @endif
                            <button class="px-3 py-1.5 bg-primary-600 text-white text-xs font-medium rounded-lg hover:bg-primary-700 transition"
                                onclick="event.preventDefault(); addToCart('{{ $product->id }}', '{{ e($product->name) }}', '{{ $stock ? $stock->sale_price : 0 }}')">
                                Add
                            </button>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($products->hasPages())
            <div class="mt-8 flex justify-center">
                {{ $products->withQueryString()->links('pagination::tailwind') }}
            </div>
            @endif

            @else
            <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <div class="text-5xl mb-4">🔍</div>
                <h3 class="text-lg font-semibold mb-2">No products found</h3>
                <p class="text-sm text-gray-500 mb-4">Try adjusting your search or filters</p>
                <a href="{{ route('store.products') }}" class="px-5 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 transition">Clear Filters</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function addToCart(id, name, price) {
        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
        let existing = cart.find(i => i.id == id);
        if (existing) { existing.qty += 1; }
        else { cart.push({ id, name, price: parseFloat(price), qty: 1 }); }
        localStorage.setItem('cart', JSON.stringify(cart));
        localStorage.setItem('cartCount', cart.reduce((s, i) => s + i.qty, 0));
        alert('Added to cart!');
    }
</script>
@endsection
