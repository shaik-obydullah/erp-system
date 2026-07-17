@extends('storefront.layout')
@section('title', 'Cart')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8" x-data="cartPage()">
    <h1 class="text-2xl font-bold mb-8">Shopping Cart</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cart Items -->
        <div class="lg:col-span-2">
            <div x-show="items.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center" x-cloak>
                <div class="text-5xl mb-4">🛒</div>
                <h3 class="text-lg font-semibold mb-2">Your cart is empty</h3>
                <p class="text-sm text-gray-500 mb-4">Add some products to get started.</p>
                <a href="{{ route('store.products') }}" class="px-6 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 transition">Browse Products</a>
            </div>

            <div x-show="items.length > 0" class="space-y-4" x-cloak>
                <template x-for="(item, index) in items" :key="item.id">
                    <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-5">
                        <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-3xl opacity-30">📦</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-sm line-clamp-1" x-text="item.name"></h3>
                            <p class="text-primary-600 font-bold mt-1" x-text="'${{ $currencySymbol }}' + parseFloat(item.price).toFixed(2)"></p>
                        </div>
                        <div class="flex items-center border border-gray-300 rounded-lg flex-shrink-0">
                            <button @click="updateQty(index, -1)" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-100 transition rounded-l-lg text-sm">−</button>
                            <span class="px-3 py-1.5 text-sm font-medium min-w-[2rem] text-center" x-text="item.qty"></span>
                            <button @click="updateQty(index, 1)" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-100 transition rounded-r-lg text-sm">+</button>
                        </div>
                        <div class="text-right flex-shrink-0 w-24">
                            <p class="font-bold text-sm" x-text="'${{ $currencySymbol }}' + (item.price * item.qty).toFixed(2)"></p>
                        </div>
                        <button @click="removeItem(index)" class="text-red-400 hover:text-red-600 transition flex-shrink-0 p-1">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Summary -->
        <div x-show="items.length > 0" x-cloak>
            <div class="bg-white rounded-xl border border-gray-200 p-6 sticky top-24">
                <h2 class="font-bold text-lg mb-4">Order Summary</h2>
                <div class="space-y-3 text-sm mb-6">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Subtotal (<span x-text="totalItems"></span> items)</span>
                        <span class="font-medium" x-text="'${{ $currencySymbol }}' + subtotal().toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Shipping</span>
                        <span class="font-medium text-green-600">Free</span>
                    </div>
                    <div class="border-t pt-3 flex justify-between">
                        <span class="font-bold">Total</span>
                        <span class="font-bold text-lg text-primary-600" x-text="'${{ $currencySymbol }}' + subtotal().toFixed(2)"></span>
                    </div>
                </div>
                <a href="{{ route('store.checkout') }}"
                   class="block w-full py-3 bg-primary-600 text-white text-center font-semibold rounded-lg hover:bg-primary-700 transition shadow-md">
                    Proceed to Checkout
                </a>
                <a href="{{ route('store.products') }}" class="block text-center text-sm text-primary-600 mt-3 hover:underline">Continue Shopping</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function cartPage() {
        return {
            items: JSON.parse(localStorage.getItem('cart') || '[]'),
            get totalItems() { return this.items.reduce((s, i) => s + i.qty, 0); },
            subtotal() { return this.items.reduce((s, i) => s + (i.price * i.qty), 0); },
            updateQty(index, delta) {
                this.items[index].qty += delta;
                if (this.items[index].qty <= 0) this.items.splice(index, 1);
                this.save();
            },
            removeItem(index) {
                this.items.splice(index, 1);
                this.save();
            },
            save() {
                localStorage.setItem('cart', JSON.stringify(this.items));
                localStorage.setItem('cartCount', this.totalItems);
            }
        }
    }
</script>
@endsection
