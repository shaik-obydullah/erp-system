@extends('storefront.layout')
@section('title', 'Checkout')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8" x-data="checkoutPage()">
    <h1 class="text-2xl font-bold mb-8">Checkout</h1>

    <div x-show="items.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center" x-cloak>
        <div class="text-5xl mb-4">🛒</div>
        <h3 class="text-lg font-semibold mb-2">Your cart is empty</h3>
        <a href="{{ route('store.products') }}" class="px-6 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 transition">Browse Products</a>
    </div>

    <div x-show="items.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-8" x-cloak>
        <!-- Shipping Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="font-bold text-lg mb-5">Shipping Information</h2>
                <form id="checkoutForm" class="space-y-4" novalidate>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="customer_name" class="block text-sm font-medium mb-1"
                                :class="errors.customer_name ? 'text-red-600' : 'text-gray-700'">Full Name *</label>
                            <input type="text" id="customer_name" name="customer_name" x-model="form.customer_name" @input="clearError('customer_name')"
                                class="w-full px-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:outline-none"
                                :class="errors.customer_name ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-primary-500'">
                            <p x-show="errors.customer_name" x-text="errors.customer_name" class="mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label for="customer_email" class="block text-sm font-medium mb-1"
                                :class="errors.customer_email ? 'text-red-600' : 'text-gray-700'">Email *</label>
                            <input type="email" id="customer_email" name="customer_email" x-model="form.customer_email" @input="clearError('customer_email')"
                                class="w-full px-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:outline-none"
                                :class="errors.customer_email ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-primary-500'">
                            <p x-show="errors.customer_email" x-text="errors.customer_email" class="mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label for="customer_phone" class="block text-sm font-medium mb-1"
                                :class="errors.customer_phone ? 'text-red-600' : 'text-gray-700'">Phone *</label>
                            <input type="text" id="customer_phone" name="customer_phone" x-model="form.customer_phone" @input="clearError('customer_phone')"
                                class="w-full px-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:outline-none"
                                :class="errors.customer_phone ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-primary-500'">
                            <p x-show="errors.customer_phone" x-text="errors.customer_phone" class="mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label for="address" class="block text-sm font-medium mb-1"
                                :class="errors.address ? 'text-red-600' : 'text-gray-700'">Address *</label>
                            <input type="text" id="address" name="address" x-model="form.address" @input="clearError('address')"
                                class="w-full px-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:outline-none"
                                :class="errors.address ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-primary-500'">
                            <p x-show="errors.address" x-text="errors.address" class="mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label for="city" class="block text-sm font-medium mb-1"
                                :class="errors.city ? 'text-red-600' : 'text-gray-700'">City *</label>
                            <input type="text" id="city" name="city" x-model="form.city" @input="clearError('city')"
                                class="w-full px-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:outline-none"
                                :class="errors.city ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-primary-500'">
                            <p x-show="errors.city" x-text="errors.city" class="mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" x-model="form.postal_code"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        </div>
                        <div>
                            <label for="country" class="block text-sm font-medium mb-1"
                                :class="errors.country ? 'text-red-600' : 'text-gray-700'">Country *</label>
                            <select id="country" name="country" x-model="form.country" @change="loadShippingMethods()"
                                class="w-full px-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:outline-none"
                                :class="errors.country ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-primary-500'">
                                <option value="">Select country...</option>
                                <template x-for="(cname, code) in countries" :key="code">
                                    <option :value="code" x-text="cname"></option>
                                </template>
                            </select>
                            <p x-show="errors.country" x-text="errors.country" class="mt-1 text-xs text-red-600"></p>
                        </div>
                    </div>

                    <!-- Shipping Method -->
                    <div class="mt-6 pt-6 border-t" x-show="shippingMethods.length > 0">
                        <h3 class="font-bold mb-3">Shipping Method</h3>
                        <div x-show="shippingLoading" class="text-sm text-gray-500 mb-2">Calculating shipping options...</div>
                        <div x-show="!shippingLoading && shippingMethods.length > 0" class="space-y-2">
                            <template x-for="m in shippingMethods" :key="m.id">
                                <label class="flex items-center gap-3 p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                       :class="{ 'border-primary-500 bg-primary-50': selectedShippingId === m.id }">
                                    <input type="radio" name="shipping_method" :value="m.id" x-model="selectedShippingId" class="text-primary-600 focus:ring-primary-500">
                                    <div class="flex-1">
                                        <span class="font-medium text-sm" x-text="m.name"></span>
                                        <p class="text-xs text-gray-400" x-show="m.type === 'free_shipping'" x-text="'Free on orders over $' + Number(m.min_order_amount).toFixed(2)"></p>
                                    </div>
                                    <span class="font-medium text-sm" x-text="m.cost > 0 ? '{{ $currencySymbol }}' + Number(m.cost).toFixed(2) : 'Free'"></span>
                                </label>
                            </template>
                        </div>
                        <div x-show="!shippingLoading && !shippingMethods.length && form.country"
                            class="text-sm text-orange-500 bg-orange-50 border border-orange-200 rounded-lg p-3">
                            No shipping methods available for this country yet.
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="font-bold mb-3">Payment Method</h3>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                   :class="{ 'border-primary-500 bg-primary-50': paymentMethod === 'cod' }">
                                <input type="radio" name="payment_method" value="cod" x-model="paymentMethod" class="text-primary-600 focus:ring-primary-500">
                                <div>
                                    <span class="font-medium text-sm">Cash on Delivery</span>
                                    <p class="text-xs text-gray-400">Pay when you receive your order</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                   :class="{ 'border-primary-500 bg-primary-50': paymentMethod === 'bank' }">
                                <input type="radio" name="payment_method" value="bank" x-model="paymentMethod" class="text-primary-600 focus:ring-primary-500">
                                <div>
                                    <span class="font-medium text-sm">Bank Transfer</span>
                                    <p class="text-xs text-gray-400">Transfer directly to our bank account</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order Notes (Optional)</label>
                        <textarea name="notes" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" placeholder="Any special instructions..."></textarea>
                    </div>
                </form>
            </div>
        </div>

        <!-- Order Summary -->
        <div>
            <div class="bg-white rounded-xl border border-gray-200 p-6 sticky top-24">
                <h2 class="font-bold text-lg mb-4">Order Summary</h2>
                <div class="space-y-3 mb-4 max-h-60 overflow-y-auto">
                    <template x-for="item in items" :key="item.id">
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center flex-shrink-0">
                                <span class="text-lg opacity-30">📦</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium truncate text-xs" x-text="item.name"></p>
                                <p class="text-gray-400 text-xs">Qty: <span x-text="item.qty"></span></p>
                            </div>
                            <span class="font-medium text-xs flex-shrink-0" x-text="'{{ $currencySymbol }}' + (item.price * item.qty).toFixed(2)"></span>
                        </div>
                    </template>
                </div>
                <div class="border-t pt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-medium" x-text="'{{ $currencySymbol }}' + subtotal().toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Shipping</span>
                        <span x-show="selectedShipping" class="font-medium"
                              :class="selectedShippingCost > 0 ? '' : 'text-green-600'"
                              x-text="selectedShippingCost > 0 ? '{{ $currencySymbol }}' + selectedShippingCost.toFixed(2) : 'Free'"></span>
                        <span x-show="!selectedShipping" class="font-medium text-gray-400">Calculated at checkout</span>
                    </div>
                    <div class="border-t pt-2 flex justify-between">
                        <span class="font-bold">Total</span>
                        <span class="font-bold text-lg text-primary-600" x-text="'{{ $currencySymbol }}' + total().toFixed(2)"></span>
                    </div>
                </div>

                <button @click="placeOrder()"
                    class="w-full mt-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition shadow-md flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
                    Place Order
                </button>

                <p class="text-xs text-gray-400 text-center mt-3">By placing your order, you agree to our terms and conditions.</p>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccess" x-cloak x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full text-center shadow-2xl">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-xl font-bold mb-2">Order Placed!</h3>
            <p class="text-sm text-gray-500 mb-6">Thank you for your order. Your order number is <strong x-text="orderNumber"></strong>.</p>
            <a href="{{ route('store.home') }}" class="px-6 py-2.5 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition">
                Continue Shopping
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function checkoutPage() {
        return {
            items: JSON.parse(localStorage.getItem('cart') || '[]'),
            paymentMethod: 'cod',
            showSuccess: false,
            orderNumber: '',
            form: {
                customer_name: '',
                customer_email: '',
                customer_phone: '',
                address: '',
                city: '',
                postal_code: '',
                country: '',
                notes: '',
            },
            countries: @json($countries),
            shippingMethods: [],
            selectedShippingId: null,
            shippingLoading: false,
            errors: {},
            get selectedShipping() {
                return this.shippingMethods.find(m => m.id === this.selectedShippingId) || null;
            },
            get selectedShippingCost() {
                return this.selectedShipping ? Number(this.selectedShipping.cost) : 0;
            },
            subtotal() { return this.items.reduce((s, i) => s + (i.price * i.qty), 0); },
            total() { return this.subtotal() + this.selectedShippingCost; },
            clearError(field) {
                if (this.errors[field]) {
                    delete this.errors[field];
                }
            },
            async loadShippingMethods() {
                if (!this.form.country) {
                    this.shippingMethods = [];
                    this.selectedShippingId = null;
                    return;
                }
                this.shippingLoading = true;
                this.shippingMethods = [];
                this.selectedShippingId = null;
                try {
                    const response = await fetch(`{{ route('store.shipping.options') }}?country=${encodeURIComponent(this.form.country)}&subtotal=${this.subtotal().toFixed(2)}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    if (data.methods && data.methods.length > 0) {
                        this.shippingMethods = data.methods;
                        this.selectedShippingId = data.methods[0].id;
                    }
                } catch (e) {
                    this.shippingMethods = [];
                } finally {
                    this.shippingLoading = false;
                }
            },
            validate() {
                this.errors = {};
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const rules = {
                    customer_name: 'Please enter your full name',
                    customer_email: 'Please enter your email address',
                    customer_phone: 'Please enter your phone number',
                    address: 'Please enter your address',
                    city: 'Please enter your city',
                    country: 'Please select your country',
                };
                for (const [field, message] of Object.entries(rules)) {
                    const value = (this.form[field] || '').trim();
                    if (!value) {
                        this.errors[field] = message;
                    } else if (field === 'customer_email' && !emailPattern.test(value)) {
                        this.errors[field] = 'Please enter a valid email address';
                    }
                }
                return Object.keys(this.errors).length === 0;
            },
            placeOrder() {
                if (!this.validate()) return;

                this.orderNumber = 'ORD-' + Date.now().toString(36).toUpperCase();
                this.showSuccess = true;
                localStorage.removeItem('cart');
                localStorage.setItem('cartCount', '0');
            }
        }
    }
</script>
@endsection
