@extends('storefront.layout')
@section('title', 'Vendors')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Sell on ShopHub Registration --}}
    <h1 class="text-3xl font-bold mb-2 text-gray-900">Sell on ShopHub</h1>
    <p class="text-gray-900 text-lg mb-6">Fill up this form to sell on ShopHub</p>

    <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-2xl p-8 mb-10">
        <div class="max-w-2xl">
            <div x-data="vendorForm()" class="bg-white rounded-xl p-6 text-gray-900">
                @if(session('vendor_success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-4">
                    {{ session('vendor_success') }}
                </div>
                @endif

                <form action="{{ route('store.vendor.register') }}" method="POST" x-show="!submitted" x-transition>
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Business Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none text-sm"
                                placeholder="Your business name">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none text-sm"
                                placeholder="you@business.com">
                            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile</label>
                            <input type="text" name="mobile" value="{{ old('mobile') }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none text-sm"
                                placeholder="+1 234 567 890">
                            @error('mobile') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <input type="text" name="address" value="{{ old('address') }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none text-sm"
                                placeholder="City, Country">
                            @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                            <input type="password" name="password" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none text-sm"
                                placeholder="Min 8 characters">
                            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                            <input type="password" name="password_confirmation" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none text-sm"
                                placeholder="Re-enter password">
                        </div>
                    </div>
                    <button type="submit"
                        class="mt-8 px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition font-medium text-sm">
                        Register as Seller
                    </button>
                </form>

                @if(session('vendor_success') || old('name'))
                <div class="text-center py-4">
                    <div class="text-4xl mb-3">🎉</div>
                    <p class="text-gray-600">Your vendor application has been submitted! You can now log in at the Supplier Portal.</p>
                    <a href="{{ route('supplier.login') }}" class="inline-block mt-3 px-5 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition text-sm font-medium">
                        Go to Supplier Login
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <h2 class="text-2xl font-bold mb-2">Our Vendors</h2>
    <p class="text-sm text-gray-500 mb-8">{{ $vendors->total() }} vendors registered</p>

    @if($vendors->count())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($vendors as $vendor)
        <a href="{{ route('store.vendor', $vendor->name) }}"
           class="group bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg hover:border-primary-300 transition-all duration-200">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0 group-hover:bg-primary-200 transition">
                    <span class="text-2xl font-bold text-primary-700">{{ substr($vendor->name, 0, 1) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-lg group-hover:text-primary-600 transition">{{ $vendor->name }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $vendor->address ?? 'No address listed' }}</p>
                    <p class="text-sm text-gray-400 mt-0.5">{{ $vendor->mobile ?? '' }}</p>
                    <p class="text-sm text-gray-400">{{ $vendor->email ?? '' }}</p>
                </div>
            </div>
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                <span class="text-xs text-gray-400">View Store →</span>
                <div class="flex items-center gap-1 text-accent-500">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                    <span class="text-sm font-medium">Verified Vendor</span>
                </div>
            </div>
        </a>
        @endforeach
    </div>

    @if($vendors->hasPages())
    <div class="mt-8 flex justify-center">
        {{ $vendors->links('pagination::tailwind') }}
    </div>
    @endif

    @else
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <div class="text-5xl mb-4">🏪</div>
        <h3 class="text-lg font-semibold mb-2">No vendors found</h3>
        <p class="text-sm text-gray-500">Check back later for new vendors.</p>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
function vendorForm() {
    return {
        submitted: {{ session('vendor_success') ? 'true' : 'false' }},
    }
}
</script>
@endsection
