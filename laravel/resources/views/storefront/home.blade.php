@extends('storefront.layout')

@section('title', 'Home')

@section('content')
<div x-data="homePage()">

    <!-- Hero Carousel -->
    <section class="relative overflow-hidden text-white"
        x-data="{
            currentSlide: 0,
            totalSlides: {{ $heroContent->count() ? 5 : 4 }},
            autoplayTimer: null,
            slides: [
                @if($heroContent->count())
                {
                    bg: 'from-primary-600 via-primary-700 to-primary-900',
                    title: '{!! nl2br(e($heroContent->first()->name)) !!}',
                    subtitle: '{{ $heroContent->first()->attribute ?? "" }}',
                    description: '{!! nl2br(e($heroContent->first()->content ?? "")) !!}',
                    buttons: [
                        { text: 'Shop Now', href: '{{ route("store.products") }}', style: 'bg-white text-primary-700 hover:bg-gray-100' },
                        { text: 'Browse Vendors', href: '{{ route("store.vendors") }}', style: 'border-2 border-white/40 text-white hover:bg-white/10' }
                    ]
                },
                @endif
                {
                    bg: 'from-primary-600 via-primary-700 to-primary-900',
                    title: 'Discover. Shop. From Multiple Vendors.',
                    subtitle: '',
                    description: 'Explore thousands of products from verified sellers. Quality guaranteed, fast shipping, and unbeatable prices.',
                    buttons: [
                        { text: 'Shop Now', href: '{{ route("store.products") }}', style: 'bg-white text-primary-700 hover:bg-gray-100' },
                        { text: 'Browse Vendors', href: '{{ route("store.vendors") }}', style: 'border-2 border-white/40 text-white hover:bg-white/10' }
                    ]
                },
                {
                    bg: 'from-purple-600 via-purple-700 to-purple-900',
                    title: 'New Season Collection',
                    subtitle: '',
                    description: 'Fresh styles just arrived. Be the first to rock the latest trends this season.',
                    buttons: [
                        { text: 'Shop Now', href: '{{ route("store.products") }}', style: 'bg-white text-purple-700 hover:bg-gray-100' }
                    ]
                },
                {
                    bg: 'from-orange-500 via-amber-500 to-yellow-600',
                    title: 'Flash Deals - Up to 50% Off',
                    subtitle: '',
                    description: 'Limited time offers on top products. Grab them before they are gone!',
                    buttons: [
                        { text: 'Shop Now', href: '{{ route("store.products") }}', style: 'bg-white text-orange-700 hover:bg-gray-100' }
                    ]
                },
                {
                    bg: 'from-teal-600 via-teal-700 to-teal-900',
                    title: 'Sell on ShopHub',
                    subtitle: '',
                    description: 'Join thousands of vendors. Set up your store and start selling today.',
                    buttons: [
                        { text: 'Become a Vendor', href: '{{ route("store.vendors") }}', style: 'bg-white text-teal-700 hover:bg-gray-100' }
                    ]
                }
            ],
            init() {
                this.startAutoplay();
            },
            startAutoplay() {
                this.stopAutoplay();
                this.autoplayTimer = setInterval(() => {
                    this.next();
                }, 5000);
            },
            stopAutoplay() {
                if (this.autoplayTimer) {
                    clearInterval(this.autoplayTimer);
                    this.autoplayTimer = null;
                }
            },
            next() {
                this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
            },
            prev() {
                this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
            },
            goTo(index) {
                this.currentSlide = index;
                this.startAutoplay();
            }
        }"
        @mouseenter="stopAutoplay()"
        @mouseleave="startAutoplay()">

        <!-- Slides -->
        <template x-for="(slide, index) in slides" :key="index">
            <div class="absolute inset-0 transition-opacity duration-700 ease-in-out"
                :class="{ 'opacity-100 z-10': currentSlide === index, 'opacity-0 z-0': currentSlide !== index }">
                <div class="absolute inset-0 bg-gradient-to-br" :class="slide.bg"></div>
                <div class="relative max-w-7xl mx-auto px-4 py-16 md:py-24">
                    <div class="max-w-2xl">
                        <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-4" x-html="slide.title"></h1>
                        <template x-if="slide.subtitle">
                            <p class="text-lg text-white/80 mb-2" x-text="slide.subtitle"></p>
                        </template>
                        <template x-if="slide.description">
                            <p class="text-white/70 mb-8" x-text="slide.description"></p>
                        </template>
                        <div class="flex flex-wrap gap-3">
                            <template x-for="(btn, bIndex) in slide.buttons" :key="bIndex">
                                <a :href="btn.href"
                                    class="px-6 py-3 font-semibold rounded-lg transition shadow-lg"
                                    :class="btn.style"
                                    x-text="btn.text"></a>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Arrow Navigation -->
        <button class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-white/20 hover:bg-white/40 backdrop-blur-sm rounded-full flex items-center justify-center transition opacity-0 group-hover:opacity-100 md:opacity-0"
            @click="prev(); startAutoplay()">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <button class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-white/20 hover:bg-white/40 backdrop-blur-sm rounded-full flex items-center justify-center transition opacity-0 group-hover:opacity-100 md:opacity-0"
            @click="next(); startAutoplay()">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path d="M9 5l7 7-7 7"/>
            </svg>
        </button>

        <!-- Dots Navigation -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2">
            <template x-for="(slide, index) in slides" :key="index">
                <button class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                    :class="currentSlide === index ? 'bg-white w-6' : 'bg-white/40 hover:bg-white/60'"
                    @click="goTo(index)"></button>
            </template>
        </div>
    </section>

    <!-- Stats -->
    <section class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div>
                    <div class="text-2xl font-bold text-primary-600">{{ number_format($products_count ?? 16) }}+</div>
                    <div class="text-sm text-gray-500">Products</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-primary-600">{{ $vendors->count() }}+</div>
                    <div class="text-sm text-gray-500">Vendors</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-primary-600">{{ $categories->count() }}</div>
                    <div class="text-sm text-gray-500">Categories</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-primary-600">24/7</div>
                    <div class="text-sm text-gray-500">Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories -->
    @if($categories->count())
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">Shop by Category</h2>
            <a href="{{ route('store.products') }}" class="text-primary-600 text-sm font-medium hover:underline">View All →</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($categories as $cat)
            <a href="{{ route('store.products', ['category' => $cat->id]) }}"
               class="group bg-white rounded-xl border border-gray-200 p-5 text-center hover:shadow-lg hover:border-primary-300 transition-all duration-200">
                <div class="w-14 h-14 mx-auto mb-3 bg-primary-50 rounded-full flex items-center justify-center group-hover:bg-primary-100 transition">
                    <svg class="w-7 h-7 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-sm group-hover:text-primary-600 transition">{{ $cat->name }}</h3>
                <p class="text-xs text-gray-400 mt-1">{{ $cat->products_count }} products</p>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Featured Products -->
    @if($featuredProducts->count())
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">Featured Products</h2>
            <a href="{{ route('store.products') }}" class="text-primary-600 text-sm font-medium hover:underline">View All →</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($featuredProducts as $product)
            @php $stock = $product->stocks->first(); @endphp
            <a href="{{ route('store.product', $product->url_slug) }}"
               class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center p-6 relative">
                    <div class="text-6xl opacity-20 group-hover:opacity-30 transition">
                        @if($product->category)
                            @switch($product->category->name)
                                @case('Phones') 📱 @break
                                @case('Laptops') 💻 @break
                                @case('Audio') 🎧 @break
                                @case('Shoes') 👟 @break
                                @default 📦 @endswitch
                        @else 📦 @endif
                    </div>
                    @if($product->review_avg >= 4.5)
                    <span class="absolute top-3 left-3 bg-accent-500 text-white text-xs font-bold px-2 py-1 rounded-full">Best Seller</span>
                    @endif
                    @if($stock && $product->brand)
                    <span class="absolute top-3 right-3 bg-gray-900/70 text-white text-xs px-2 py-1 rounded-full">{{ $product->brand->name }}</span>
                    @endif
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-sm line-clamp-2 group-hover:text-primary-600 transition mb-1">{{ $product->name }}</h3>
                    @if($product->supplier)
                    <p class="text-xs text-gray-400 mb-2">by {{ $product->supplier->name }}</p>
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
                    <div class="flex items-center justify-between">
                        @if($stock)
                        <span class="text-lg font-bold text-primary-600">{{ $currencySymbol }}{{ number_format($stock->sale_price, 2) }}</span>
                        @endif
                        <button class="px-3 py-1.5 bg-primary-600 text-white text-xs font-medium rounded-lg hover:bg-primary-700 transition"
                            onclick="event.preventDefault(); addToCart('{{ $product->id }}', '{{ e($product->name) }}', '{{ $stock ? $stock->sale_price : 0 }}')">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    <!-- New Arrivals -->
    @if($newArrivals->count())
    <section class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold">New Arrivals</h2>
                <a href="{{ route('store.products') }}" class="text-primary-600 text-sm font-medium hover:underline">View All →</a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                @foreach($newArrivals as $product)
                @php $stock = $product->stocks->first(); @endphp
                <a href="{{ route('store.product', $product->url_slug) }}"
                   class="group bg-gray-50 rounded-xl overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="aspect-square bg-gradient-to-br from-gray-100 to-white flex items-center justify-center p-6">
                        <div class="text-5xl opacity-20 group-hover:opacity-30 transition">🆕</div>
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-sm line-clamp-2 group-hover:text-primary-600 transition mb-1">{{ $product->name }}</h3>
                        @if($product->supplier)
                        <p class="text-xs text-gray-400 mb-2">{{ $product->supplier->name }}</p>
                        @endif
                        <div class="flex items-center gap-2">
                            @if($stock)
                            <span class="text-lg font-bold text-primary-600">{{ $currencySymbol }}{{ number_format($stock->sale_price, 2) }}</span>
                            @endif
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-medium">New</span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Vendors -->
    @if($vendors->count())
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">Top Vendors</h2>
            <a href="{{ route('store.vendors') }}" class="text-primary-600 text-sm font-medium hover:underline">View All →</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($vendors as $vendor)
            <a href="{{ route('store.vendor', $vendor->name) }}"
               class="group flex items-center gap-4 bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg hover:border-primary-300 transition-all duration-200">
                <div class="w-14 h-14 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0 group-hover:bg-primary-200 transition">
                    <span class="text-xl font-bold text-primary-700">{{ substr($vendor->name, 0, 1) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold group-hover:text-primary-600 transition truncate">{{ $vendor->name }}</h3>
                    <p class="text-sm text-gray-400 truncate">{{ $vendor->address ?? 'No address listed' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $vendor->mobile ?? '' }}</p>
                </div>
                <svg class="w-5 h-5 text-gray-300 group-hover:text-primary-500 transition flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    <!-- FAQ Section -->
    @if($faqContent->count())
    <section class="max-w-4xl mx-auto px-4 py-12">
        <h2 class="text-2xl font-bold text-center mb-8">Frequently Asked Questions</h2>
        <div class="space-y-4" x-data="{ openFaq: null }">
            @foreach($faqContent as $faq)
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <button class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50 transition"
                    @click="openFaq = openFaq === {{ $faq->id }} ? null : {{ $faq->id }}">
                    <span class="font-semibold text-gray-800">{{ $faq->name }}</span>
                    <svg class="w-5 h-5 text-gray-400 transform transition-transform"
                        :class="{ 'rotate-180': openFaq === {{ $faq->id }} }"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === {{ $faq->id }}" x-collapse>
                    <div class="px-6 pb-4 text-gray-600 text-sm leading-relaxed">
                        {!! nl2br(e($faq->content ?? '')) !!}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Newsletter CTA -->
    <section class="bg-primary-600 py-12">
        <div class="max-w-3xl mx-auto px-4 text-center text-white">
            <h2 class="text-2xl font-bold mb-2">Stay Updated</h2>
            <p class="text-primary-100 mb-6">Get the latest deals and new product alerts delivered to your inbox.</p>
            <div class="flex max-w-md mx-auto">
                <input type="email" placeholder="Enter your email" class="flex-1 px-4 py-3 rounded-l-lg text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500">
                <button class="px-6 py-3 bg-accent-500 text-white font-semibold rounded-r-lg hover:bg-accent-600 transition">Subscribe</button>
            </div>
        </div>
    </section>

</div>
@endsection

@section('scripts')
<script>
    function homePage() {
        return {}
    }

    function addToCart(id, name, price) {
        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
        let existing = cart.find(i => i.id == id);
        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({ id, name, price: parseFloat(price), qty: 1 });
        }
        localStorage.setItem('cart', JSON.stringify(cart));
        localStorage.setItem('cartCount', cart.reduce((s, i) => s + i.qty, 0));
        window.dispatchEvent(new Event('storage'));
        location.reload();
    }
</script>
@endsection
