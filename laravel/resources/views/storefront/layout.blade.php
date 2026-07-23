<!DOCTYPE html>
<html lang="en" x-data="storeApp()" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ShopHub') — Multi-Vendor Marketplace</title>
    <link rel="stylesheet" href="/css/storefront.css?v={{ md5_file(public_path('css/storefront.css')) }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    @yield('head')
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

    <!-- Top Bar -->
    <div class="bg-gray-900 text-gray-300 text-xs">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-8">
            <span>Free shipping on orders over $50</span>
            <div class="flex items-center gap-4">
                <a href="{{ route('store.vendors') }}" class="hover:text-white transition">Sell on ShopHub</a>
                <a href="{{ route('dashboard') }}" class="hover:text-white transition">Admin Panel</a>
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="{{ route('store.home') }}" class="flex items-center gap-2">
                    <div class="w-9 h-9 bg-primary-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900">Shop<span class="text-primary-600">Hub</span></span>
                </a>

                <!-- Search -->
                <div class="hidden md:flex flex-1 max-w-xl mx-8">
                    <form action="{{ route('store.products') }}" method="GET" class="w-full flex">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products, brands, vendors..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        <button type="submit" class="px-5 bg-primary-600 text-white rounded-r-lg hover:bg-primary-700 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        </button>
                    </form>
                </div>

                <!-- Right -->
                <div class="flex items-center gap-5">
                    <a href="{{ route('store.cart') }}" class="relative p-2 text-gray-600 hover:text-primary-600 transition">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        <span x-show="cartCount > 0" x-text="cartCount"
                            class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold"
                            x-cloak></span>
                    </a>
                    <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div x-show="mobileMenu" x-cloak x-transition class="md:hidden border-t bg-white">
            <div class="px-4 py-3">
                <form action="{{ route('store.products') }}" method="GET" class="flex mb-3">
                    <input type="text" name="search" placeholder="Search..." class="w-full px-3 py-2 border rounded-l-lg text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <button class="px-4 bg-primary-600 text-white rounded-r-lg text-sm">Search</button>
                </form>
                <a href="{{ route('store.home') }}" class="block py-2 text-sm font-medium hover:text-primary-600">Home</a>
                <a href="{{ route('store.products') }}" class="block py-2 text-sm font-medium hover:text-primary-600">Shop All</a>
                <a href="{{ route('store.products') }}?category=electronics" class="block py-2 text-sm font-medium hover:text-primary-600 pl-4">Electronics</a>
                <a href="{{ route('store.products') }}?category=fashion" class="block py-2 text-sm font-medium hover:text-primary-600 pl-4">Fashion</a>
                <a href="{{ route('store.products') }}?category=home" class="block py-2 text-sm font-medium hover:text-primary-600 pl-4">Home & Living</a>
                <a href="{{ route('store.products') }}?category=sports" class="block py-2 text-sm font-medium hover:text-primary-600 pl-4">Sports</a>
                <a href="{{ route('store.products') }}?category=beauty" class="block py-2 text-sm font-medium hover:text-primary-600 pl-4">Beauty</a>
                <a href="{{ route('store.products') }}?category=books" class="block py-2 text-sm font-medium hover:text-primary-600 pl-4">Books</a>
                <div class="border-t my-2"></div>
                <a href="{{ route('store.products') }}?sort=newest" class="block py-2 text-sm font-medium hover:text-primary-600">New Arrivals</a>
                <a href="{{ route('store.vendors') }}" class="block py-2 text-sm font-medium hover:text-primary-600">Vendors</a>
            </div>
        </div>
    </nav>

    <!-- Desktop Mega Menu Nav -->
    <div class="hidden md:block bg-white border-b" x-data="megaMenu()" x-cloak>
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center gap-1 h-11 text-sm font-medium">

                <!-- Shop with mega dropdown -->
                <div class="relative"
                    @mouseenter="openMega('shop')" @mouseleave="closeMega('shop')">
                    <a href="{{ route('store.products') }}"
                        :class="activeMega === 'shop' ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50'"
                        class="flex items-center gap-1 px-4 py-2.5 rounded-lg transition-all duration-200">
                        Shop
                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="activeMega === 'shop' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M19 9l-7 7-7-7"/></svg>
                    </a>

                    <!-- Shop Mega Panel -->
                    <div x-show="activeMega === 'shop'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute top-full left-1/2 -translate-x-1/2 w-[780px] bg-white rounded-xl shadow-2xl border border-gray-100 mt-2 p-6"
                        @mouseenter="keepOpen('shop')" @mouseleave="closeMega('shop')">
                        <div class="grid grid-cols-3 gap-6">
                            <!-- Left: Category Grid -->
                            <div class="col-span-2">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Categories</p>
                                <div class="grid grid-cols-3 gap-2">
                                    <a href="{{ route('store.products') }}?category=electronics"
                                        class="flex items-center gap-3 p-3 rounded-lg hover:bg-primary-50 hover:text-primary-700 transition group">
                                        <span class="text-2xl">🔌</span>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 group-hover:text-primary-700">Electronics</p>
                                            <p class="text-xs text-gray-400">Gadgets & Devices</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('store.products') }}?category=fashion"
                                        class="flex items-center gap-3 p-3 rounded-lg hover:bg-primary-50 hover:text-primary-700 transition group">
                                        <span class="text-2xl">👗</span>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 group-hover:text-primary-700">Fashion</p>
                                            <p class="text-xs text-gray-400">Clothing & Accessories</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('store.products') }}?category=home"
                                        class="flex items-center gap-3 p-3 rounded-lg hover:bg-primary-50 hover:text-primary-700 transition group">
                                        <span class="text-2xl">🏠</span>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 group-hover:text-primary-700">Home & Living</p>
                                            <p class="text-xs text-gray-400">Furniture & Decor</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('store.products') }}?category=sports"
                                        class="flex items-center gap-3 p-3 rounded-lg hover:bg-primary-50 hover:text-primary-700 transition group">
                                        <span class="text-2xl">⚽</span>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 group-hover:text-primary-700">Sports</p>
                                            <p class="text-xs text-gray-400">Fitness & Outdoors</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('store.products') }}?category=beauty"
                                        class="flex items-center gap-3 p-3 rounded-lg hover:bg-primary-50 hover:text-primary-700 transition group">
                                        <span class="text-2xl">💄</span>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 group-hover:text-primary-700">Beauty</p>
                                            <p class="text-xs text-gray-400">Skincare & Makeup</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('store.products') }}?category=books"
                                        class="flex items-center gap-3 p-3 rounded-lg hover:bg-primary-50 hover:text-primary-700 transition group">
                                        <span class="text-2xl">📚</span>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 group-hover:text-primary-700">Books</p>
                                            <p class="text-xs text-gray-400">All Genres</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <!-- Right: Quick Links -->
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Featured</p>
                                    <ul class="space-y-1">
                                        <li><a href="{{ route('store.products') }}?sort=newest" class="flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600 transition py-1">
                                            <svg class="w-4 h-4 text-accent-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            New Arrivals
                                        </a></li>
                                        <li><a href="{{ route('store.products') }}?sort=price_asc" class="flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600 transition py-1">
                                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-14a1 1 0 10-2 0v2a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 5.586V4z" clip-rule="evenodd"/></svg>
                                            On Sale
                                        </a></li>
                                        <li><a href="{{ route('store.products') }}?sort=price_desc" class="flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600 transition py-1">
                                            <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z"/></svg>
                                            Top Rated
                                        </a></li>
                                    </ul>
                                </div>
                                <div class="border-t pt-3">
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Popular Products</p>
                                    <ul class="space-y-1">
                                        <li><a href="{{ route('store.products') }}?search=bestseller" class="text-sm text-gray-600 hover:text-primary-600 transition py-1 block">Best Sellers</a></li>
                                        <li><a href="{{ route('store.products') }}?search=trending" class="text-sm text-gray-600 hover:text-primary-600 transition py-1 block">Trending Now</a></li>
                                        <li><a href="{{ route('store.products') }}?search=deal" class="text-sm text-gray-600 hover:text-primary-600 transition py-1 block">Daily Deals</a></li>
                                    </ul>
                                </div>
                                <div class="border-t pt-3">
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Brands</p>
                                    <ul class="space-y-1">
                                        <li><a href="{{ route('store.products') }}?search=nike" class="text-sm text-gray-600 hover:text-primary-600 transition py-1 block">Nike</a></li>
                                        <li><a href="{{ route('store.products') }}?search=samsung" class="text-sm text-gray-600 hover:text-primary-600 transition py-1 block">Samsung</a></li>
                                        <li><a href="{{ route('store.products') }}?search=sony" class="text-sm text-gray-600 hover:text-primary-600 transition py-1 block">Sony</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="border-t mt-4 pt-4 flex items-center justify-between">
                            <a href="{{ route('store.products') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700 transition">View All Products &rarr;</a>
                            <a href="{{ route('store.vendors') }}" class="text-sm text-gray-500 hover:text-primary-600 transition">Browse Vendors</a>
                        </div>
                    </div>
                </div>

                <!-- Deals -->
                <a href="{{ route('store.products') }}?sort=price_asc"
                    class="{{ request('sort') === 'price_asc' ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }} px-4 py-2.5 rounded-lg transition-all duration-200">
                    Deals
                </a>

                <!-- New Arrivals -->
                <a href="{{ route('store.products') }}?sort=newest"
                    class="{{ request('sort') === 'newest' ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }} px-4 py-2.5 rounded-lg transition-all duration-200">
                    New Arrivals
                </a>

                <!-- Vendors -->
                <a href="{{ route('store.vendors') }}"
                    class="{{ request()->routeIs('store.vendors') || request()->routeIs('store.vendor') ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }} px-4 py-2.5 rounded-lg transition-all duration-200">
                    Vendors
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="max-w-7xl mx-auto px-4 mt-4">
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    </div>
    @endif

    <!-- Main Content -->
    <main class="min-h-screen">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        </div>
                        <span class="text-lg font-bold text-white">ShopHub</span>
                    </div>
                    <p class="text-sm">Your trusted multi-vendor marketplace. Shop from hundreds of sellers worldwide.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3 text-sm">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('store.products') }}" class="hover:text-white transition">All Products</a></li>
                        <li><a href="{{ route('store.vendors') }}" class="hover:text-white transition">Our Vendors</a></li>
                        <li><a href="{{ route('store.cart') }}" class="hover:text-white transition">Cart</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3 text-sm">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition">Return Policy</a></li>
                        <li><a href="#" class="hover:text-white transition">Contact Us</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3 text-sm">For Vendors</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('suppliers.index') }}" class="hover:text-white transition">Vendor Dashboard</a></li>
                        <li><a href="#" class="hover:text-white transition">Start Selling</a></li>
                        <li><a href="#" class="hover:text-white transition">Vendor Guidelines</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-xs">
                &copy; {{ date('Y') }} ShopHub — Multi-Vendor Marketplace. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        function storeApp() {
            return {
                mobileMenu: false,
                cartCount: parseInt(localStorage.getItem('cartCount') || '0'),
            }
        }

        function megaMenu() {
            return {
                activeMega: null,
                megaTimeout: null,

                openMega(menu) {
                    clearTimeout(this.megaTimeout);
                    this.megaTimeout = setTimeout(() => {
                        this.activeMega = menu;
                    }, 80);
                },

                closeMega(menu) {
                    clearTimeout(this.megaTimeout);
                    this.megaTimeout = setTimeout(() => {
                        if (this.activeMega === menu) {
                            this.activeMega = null;
                        }
                    }, 150);
                },

                keepOpen(menu) {
                    clearTimeout(this.megaTimeout);
                }
            }
        }
    </script>

    <!-- AI Chat Assistant -->
    @include('storefront.partials.ai-chat')

    @yield('scripts')
</body>
</html>
