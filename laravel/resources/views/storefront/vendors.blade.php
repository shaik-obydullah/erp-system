@extends('storefront.layout')
@section('title', 'Vendors')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-2">Our Vendors</h1>
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
