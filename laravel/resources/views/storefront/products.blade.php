@extends('storefront.layout')
@section('title', 'All Products')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8" x-data="productsPage()">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold">All Products</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $products->total() }} products found</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- View Toggle -->
            <div class="hidden md:flex items-center bg-white border border-gray-200 rounded-lg overflow-hidden">
                <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-primary-50 text-primary-600' : 'text-gray-400 hover:text-gray-600'" class="p-2 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                </button>
                <button @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-primary-50 text-primary-600' : 'text-gray-400 hover:text-gray-600'" class="p-2 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>

            <!-- Mobile Filter Toggle -->
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filters
            </button>
        </div>
    </div>

    <!-- Active Filters Bar -->
    @php
        $hasActiveFilters = request('search') || request('category') || request('brand') || request('min_price') || request('max_price') || (request('sort') && request('sort') !== 'newest');
        $activeFilters = [];
        if (request('search')) $activeFilters[] = ['key' => 'search', 'label' => 'Search: ' . request('search'), 'params' => array_merge(request()->except('search'), [])];
        if (request('category')) {
            $catName = $categories->firstWhere('id', request('category'))?->name ?? 'Category';
            $activeFilters[] = ['key' => 'category', 'label' => $catName, 'params' => array_merge(request()->except('category'), [])];
        }
        if (request('brand')) {
            $brandName = $brands->firstWhere('id', request('brand'))?->name ?? 'Brand';
            $activeFilters[] = ['key' => 'brand', 'label' => $brandName, 'params' => array_merge(request()->except('brand'), [])];
        }
        if (request('min_price')) $activeFilters[] = ['key' => 'min_price', 'label' => 'Min: ' . $currencySymbol . number_format(request('min_price'), 0), 'params' => array_merge(request()->except('min_price'), [])];
        if (request('max_price')) $activeFilters[] = ['key' => 'max_price', 'label' => 'Max: ' . $currencySymbol . number_format(request('max_price'), 0), 'params' => array_merge(request()->except('max_price'), [])];
        if (request('sort') && request('sort') !== 'newest') {
            $sortLabels = ['price_low' => 'Price: Low to High', 'price_high' => 'Price: High to Low', 'popular' => 'Most Popular'];
            $activeFilters[] = ['key' => 'sort', 'label' => $sortLabels[request('sort')] ?? request('sort'), 'params' => array_merge(request()->except('sort'), [])];
        }
    @endphp

    @if($hasActiveFilters)
    <div class="bg-white border border-gray-200 rounded-xl p-4 mb-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide mr-1">Active:</span>
            @foreach($activeFilters as $filter)
            <a href="{{ route('store.products', $filter['params']) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1 bg-primary-50 text-primary-700 text-xs font-medium rounded-full hover:bg-primary-100 transition group">
                {{ $filter['label'] }}
                <svg class="w-3 h-3 text-primary-400 group-hover:text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
            @endforeach
            <a href="{{ route('store.products') }}" class="text-xs text-red-500 hover:text-red-700 font-medium ml-2 transition">Clear All</a>
        </div>
    </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-8">

        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false" class="fixed inset-0 bg-black/40 z-40 lg:hidden"></div>

        <!-- Sidebar Filters -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed top-0 left-0 z-50 h-full w-72 bg-white lg:bg-transparent lg:relative lg:w-56 lg:h-auto flex-shrink-0 overflow-y-auto lg:overflow-visible transition-transform duration-300 ease-in-out">
            <div class="p-5 lg:p-0 space-y-5">

                <!-- Mobile Close -->
                <div class="flex items-center justify-between lg:hidden">
                    <h2 class="font-bold text-lg">Filters</h2>
                    <button @click="sidebarOpen = false" class="p-2 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Search -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="font-semibold text-sm mb-3">Search</h3>
                    <form action="{{ route('store.products') }}" method="GET">
                        <input type="hidden" name="category" value="{{ request('category') }}">
                        <input type="hidden" name="brand" value="{{ request('brand') }}">
                        <input type="hidden" name="min_price" value="{{ request('min_price') }}">
                        <input type="hidden" name="max_price" value="{{ request('max_price') }}">
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                        <div class="flex">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-l-lg text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <button type="submit" class="px-3 py-2 bg-primary-600 text-white rounded-r-lg hover:bg-primary-700 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Categories -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <button @click="sections.category = !sections.category" class="w-full flex items-center justify-between font-semibold text-sm mb-3">
                        <span>Categories</span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="sections.category ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="sections.category" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="space-y-1.5">
                            <a href="{{ route('store.products', array_filter(['search' => request('search'), 'brand' => request('brand'), 'min_price' => request('min_price'), 'max_price' => request('max_price'), 'sort' => request('sort')])) }}"
                               class="block text-sm px-3 py-1.5 rounded-lg {{ !request('category') ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                                All Categories
                            </a>
                            @foreach($categories as $cat)
                            <a href="{{ route('store.products', array_merge(request()->only('search', 'brand', 'min_price', 'max_price', 'sort'), ['category' => $cat->id])) }}"
                               class="block text-sm px-3 py-1.5 rounded-lg {{ request('category') == $cat->id ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                                {{ $cat->name }}
                                @if(isset($cat->products_count))
                                <span class="text-gray-400 text-xs">({{ $cat->products_count }})</span>
                                @endif
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Brands -->
                @if($brands->count())
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <button @click="sections.brand = !sections.brand" class="w-full flex items-center justify-between font-semibold text-sm mb-3">
                        <span>Brands</span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="sections.brand ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="sections.brand" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="space-y-2 max-h-48 overflow-y-auto no-scrollbar">
                            @foreach($brands as $brand)
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <input type="checkbox" name="brand" value="{{ $brand->id }}"
                                    {{ request('brand') == $brand->id ? 'checked' : '' }}
                                    onchange="handleBrandFilter(this.value, this.checked)"
                                    class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="text-sm text-gray-600 group-hover:text-gray-900 transition">{{ $brand->name }}</span>
                                @if(isset($brand->products_count))
                                <span class="text-xs text-gray-400 ml-auto">({{ $brand->products_count }})</span>
                                @endif
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Price Range -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <button @click="sections.price = !sections.price" class="w-full flex items-center justify-between font-semibold text-sm mb-3">
                        <span>Price Range</span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="sections.price ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="sections.price" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                        <form action="{{ route('store.products') }}" method="GET" id="priceFilterForm">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="category" value="{{ request('category') }}">
                            <input type="hidden" name="brand" value="{{ request('brand') }}">
                            <input type="hidden" name="sort" value="{{ request('sort') }}">
                            <div class="flex items-center gap-2">
                                <div class="flex-1">
                                    <label class="text-xs text-gray-400 mb-1 block">Min</label>
                                    <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="0" min="0" step="0.01"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                </div>
                                <span class="text-gray-400 mt-4">—</span>
                                <div class="flex-1">
                                    <label class="text-xs text-gray-400 mb-1 block">Max</label>
                                    <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="9999" min="0" step="0.01"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                </div>
                            </div>
                            <button type="submit" class="w-full mt-3 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition">Apply Price</button>
                        </form>
                    </div>
                </div>

                <!-- Sort -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <button @click="sections.sort = !sections.sort" class="w-full flex items-center justify-between font-semibold text-sm mb-3">
                        <span>Sort By</span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="sections.sort ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="sections.sort" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="space-y-1.5">
                            @php
                            $sortOptions = [
                                'newest' => 'Newest',
                                'price_low' => 'Price: Low → High',
                                'price_high' => 'Price: High → Low',
                                'popular' => 'Most Popular',
                            ];
                            @endphp
                            @foreach($sortOptions as $val => $label)
                            <a href="{{ route('store.products', array_merge(request()->except('sort'), ['sort' => $val])) }}"
                               class="block text-sm px-3 py-1.5 rounded-lg {{ (request('sort', 'newest') == $val) ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                                {{ $label }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Clear All (sidebar) -->
                @if($hasActiveFilters)
                <a href="{{ route('store.products') }}" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-red-200 text-red-600 text-sm font-medium rounded-xl hover:bg-red-50 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear All Filters
                </a>
                @endif
            </div>
        </aside>

        <!-- Product Grid -->
        <div class="flex-1">
            @if($products->count())

            <!-- Grid View -->
            <div x-show="viewMode === 'grid'" class="grid grid-cols-2 md:grid-cols-3 gap-5">
                @foreach($products as $product)
                @php $stock = $product->stocks->first(); @endphp
                <div class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative">
                    <!-- Card Image Area -->
                    <a href="{{ route('store.product', $product->url_slug) }}" class="block">
                        <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center p-6 relative overflow-hidden">
                            @if($product->first_image_url)
                                <img src="{{ $product->first_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <div class="text-5xl opacity-20 group-hover:opacity-30 transition hidden items-center justify-center">
                                    @if($product->category)
                                        @switch($product->category->name)
                                            @case('Phones') 📱 @break
                                            @case('Laptops') 💻 @break
                                            @case('Audio') 🎧 @break
                                            @case('Shoes') 👟 @break
                                            @default 📦 @endswitch
                                    @else 📦 @endif
                                </div>
                            @else
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
                            @endif
                            @if($product->brand)
                            <span class="absolute top-2 right-2 bg-gray-900/70 text-white text-xs px-2 py-0.5 rounded-full">{{ $product->brand->name }}</span>
                            @endif
                            @if($product->category)
                            <span class="absolute top-2 left-2 bg-white/80 backdrop-blur-sm text-sm px-1.5 py-0.5 rounded-full">
                                @switch($product->category->name)
                                    @case('Phones') 📱 @break
                                    @case('Laptops') 💻 @break
                                    @case('Audio') 🎧 @break
                                    @case('Shoes') 👟 @break
                                    @default 📦 @endswitch
                            </span>
                            @endif
                        </div>
                    </a>
                    <!-- Card Body -->
                    <div class="p-4">
                        <h3 class="font-semibold text-sm line-clamp-2 group-hover:text-primary-600 transition mb-1">
                            <a href="{{ route('store.product', $product->url_slug) }}">{{ $product->name }}</a>
                        </h3>
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
                    <!-- Quick View Button (hover) -->
                    <button @click="openQuickView({{ json_encode([
                        'name' => $product->name,
                        'slug' => $product->url_slug,
                        'brand' => $product->brand->name ?? null,
                        'category' => $product->category->name ?? null,
                        'price' => $stock ? $stock->sale_price : 0,
                        'sale_price' => $stock ? $stock->sale_price : null,
                        'sku' => $product->sku ?? null,
                        'description' => $product->description ?? null,
                        'review_avg' => $product->review_avg ?? 0,
                        'review_number' => $product->review_number ?? 0,
                        'id' => $product->id,
                        'in_stock' => (bool) $stock
                    ]) }})"
                        class="absolute top-12 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 translate-y-2 group-hover:translate-y-0 transition-all duration-300 px-4 py-2 bg-white/90 backdrop-blur-sm text-gray-900 text-xs font-medium rounded-lg shadow-lg hover:bg-white z-10">
                        Quick View
                    </button>
                </div>
                @endforeach
            </div>

            <!-- List View -->
            <div x-show="viewMode === 'list'" class="space-y-4">
                @foreach($products as $product)
                @php $stock = $product->stocks->first(); @endphp
                <div class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col sm:flex-row">
                    <a href="{{ route('store.product', $product->url_slug) }}" class="sm:w-48 flex-shrink-0">
                        <div class="aspect-square sm:h-full bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center p-6 relative">
                            <div class="text-4xl opacity-20 group-hover:opacity-30 transition">
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
                    </a>
                    <div class="flex-1 p-4 flex flex-col justify-between">
                        <div>
                            <h3 class="font-semibold text-sm line-clamp-2 group-hover:text-primary-600 transition mb-1">
                                <a href="{{ route('store.product', $product->url_slug) }}">{{ $product->name }}</a>
                            </h3>
                            @if($product->supplier)
                            <p class="text-xs text-gray-400 mb-2">{{ $product->supplier->name }}</p>
                            @endif
                            @if($product->description)
                            <p class="text-xs text-gray-500 line-clamp-2 mb-2">{{ $product->description }}</p>
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
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            @if($stock)
                            <span class="text-lg font-bold text-primary-600">{{ $currencySymbol }}{{ number_format($stock->sale_price, 2) }}</span>
                            @endif
                            <div class="flex items-center gap-2">
                                <button @click="openQuickView({{ json_encode([
                                    'name' => $product->name,
                                    'slug' => $product->url_slug,
                                    'brand' => $product->brand->name ?? null,
                                    'category' => $product->category->name ?? null,
                                    'price' => $stock ? $stock->sale_price : 0,
                                    'sku' => $product->sku ?? null,
                                    'description' => $product->description ?? null,
                                    'review_avg' => $product->review_avg ?? 0,
                                    'review_number' => $product->review_number ?? 0,
                                    'id' => $product->id,
                                    'in_stock' => (bool) $stock
                                ]) }}"
                                    class="px-3 py-1.5 border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition">
                                    Quick View
                                </button>
                                <button class="px-3 py-1.5 bg-primary-600 text-white text-xs font-medium rounded-lg hover:bg-primary-700 transition"
                                    onclick="event.preventDefault(); addToCart('{{ $product->id }}', '{{ e($product->name) }}', '{{ $stock ? $stock->sale_price : 0 }}')">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
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

    <!-- Quick View Modal -->
    <div x-show="quickView.open" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="quickView.open = false">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="quickView.open = false"></div>
        <!-- Modal -->
        <div x-show="quickView.open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Close -->
            <button @click="quickView.open = false" class="absolute top-4 right-4 z-10 p-2 bg-gray-100 rounded-full hover:bg-gray-200 transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="flex flex-col sm:flex-row">
                <!-- Modal Image -->
                <div class="sm:w-1/2 bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center p-8 min-h-[200px]">
                    <div class="text-7xl opacity-20">
                        <template x-if="quickView.data.category === 'Phones'"><span>📱</span></template>
                        <template x-if="quickView.data.category === 'Laptops'"><span>💻</span></template>
                        <template x-if="quickView.data.category === 'Audio'"><span>🎧</span></template>
                        <template x-if="quickView.data.category === 'Shoes'"><span>👟</span></template>
                        <template x-if="!['Phones','Laptops','Audio','Shoes'].includes(quickView.data.category)"><span>📦</span></template>
                    </div>
                </div>
                <!-- Modal Details -->
                <div class="sm:w-1/2 p-6">
                    <template x-if="quickView.data.brand">
                        <span class="inline-block bg-gray-900/70 text-white text-xs px-2 py-0.5 rounded-full mb-2" x-text="quickView.data.brand"></span>
                    </template>
                    <h2 class="text-xl font-bold mb-2" x-text="quickView.data.name"></h2>
                    <template x-if="quickView.data.sku">
                        <p class="text-xs text-gray-400 mb-2">SKU: <span x-text="quickView.data.sku"></span></p>
                    </template>
                    <!-- Rating -->
                    <template x-if="quickView.data.review_number > 0">
                        <div class="flex items-center gap-1.5 mb-3">
                            <div class="flex text-accent-500 text-xs">
                                <template x-for="i in 5" :key="i">
                                    <svg class="w-3.5 h-3.5" :class="i <= Math.floor(quickView.data.review_avg) ? 'fill-current' : 'fill-current text-gray-300'" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                </template>
                            </div>
                            <span class="text-xs text-gray-400" x-text="'(' + quickView.data.review_number + ')'"></span>
                        </div>
                    </template>
                    <!-- Price -->
                    <div class="mb-4">
                        <span class="text-2xl font-bold text-primary-600" x-text="'{{ $currencySymbol }}' + parseFloat(quickView.data.price).toFixed(2)"></span>
                    </div>
                    <!-- Description -->
                    <template x-if="quickView.data.description">
                        <p class="text-sm text-gray-500 mb-4 line-clamp-3" x-text="quickView.data.description"></p>
                    </template>
                    <!-- Stock -->
                    <div class="mb-4">
                        <template x-if="quickView.data.in_stock">
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-600 bg-green-50 px-2.5 py-1 rounded-full">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> In Stock
                            </span>
                        </template>
                        <template x-if="!quickView.data.in_stock">
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 bg-red-50 px-2.5 py-1 rounded-full">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Out of Stock
                            </span>
                        </template>
                    </div>
                    <!-- Actions -->
                    <div class="flex gap-3">
                        <a :href="'{{ route('store.products') }}/' + quickView.data.slug" class="flex-1 text-center px-4 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition">View Full Details</a>
                        <button class="px-4 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition"
                            @click="addToCart(quickView.data.id, quickView.data.name, quickView.data.price); quickView.open = false">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    function productsPage() {
        return {
            sidebarOpen: false,
            viewMode: localStorage.getItem('productViewMode') || 'grid',
            quickView: { open: false, data: {} },
            sections: {
                category: true,
                brand: true,
                price: true,
                sort: true,
            },
            init() {
                this.$watch('viewMode', (val) => localStorage.setItem('productViewMode', val));
            },
            openQuickView(data) {
                this.quickView.data = data;
                this.quickView.open = true;
            },
        }
    }

    function handleBrandFilter(brandId, checked) {
        const url = new URL(window.location.href);
        if (checked) {
            url.searchParams.set('brand', brandId);
        } else {
            url.searchParams.delete('brand');
        }
        window.location.href = url.toString();
    }

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