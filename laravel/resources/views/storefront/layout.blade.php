<!DOCTYPE html>
<html lang="en" x-data="storeApp()" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ShopHub') — Multi-Vendor Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' },
                        accent: { 500:'#f59e0b', 600:'#d97706' },
                    }
                }
            }
        }
    </script>
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
    <nav class="bg-white shadow-sm sticky top-0 z-50">
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
                <a href="{{ route('store.products') }}" class="block py-2 text-sm font-medium hover:text-primary-600">All Products</a>
                <a href="{{ route('store.vendors') }}" class="block py-2 text-sm font-medium hover:text-primary-600">Vendors</a>
            </div>
        </div>
    </nav>

    <!-- Desktop Nav Links -->
    <div class="hidden md:block bg-white border-b">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center gap-8 h-10 text-sm font-medium">
                <a href="{{ route('store.home') }}" class="{{ request()->routeIs('store.home') ? 'text-primary-600' : 'text-gray-600 hover:text-primary-600' }} transition">Home</a>
                <a href="{{ route('store.products') }}" class="{{ request()->routeIs('store.products') || request()->routeIs('store.product') ? 'text-primary-600' : 'text-gray-600 hover:text-primary-600' }} transition">All Products</a>
                <a href="{{ route('store.vendors') }}" class="{{ request()->routeIs('store.vendors') || request()->routeIs('store.vendor') ? 'text-primary-600' : 'text-gray-600 hover:text-primary-600' }} transition">Vendors</a>
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
    <footer class="bg-gray-900 text-gray-400 mt-16">
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
    </script>
    @yield('scripts')
</body>
</html>
