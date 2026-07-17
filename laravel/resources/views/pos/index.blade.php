@extends('roles.layout')

@section('title', 'Point of Sale')

@section('content')
<div x-data="posHandler()" x-init="init()">
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <h2 class="card-title">POS Terminal</h2>
            <a href="{{ route('sales.index') }}" class="btn btn-secondary">View Sales</a>
        </div>
    </div>

    <div x-show="successMessage" x-cloak class="alert alert-success show" style="margin-bottom: 16px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <span x-text="successMessage"></span>
    </div>

    <div x-show="errorMessage" x-cloak class="alert alert-error show" style="margin-bottom: 16px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
        <span x-text="errorMessage"></span>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 420px; gap: 20px; align-items: start;">

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Products</h2>
                <input type="text" class="form-input" placeholder="Search products..." x-model="searchQuery" style="max-width: 300px;">
            </div>
            <div class="card-body" style="max-height: 620px; overflow-y: auto;">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div class="card" style="cursor: pointer; margin: 0;" @click="addToCart(product)">
                            <div class="card-body" style="padding: 12px;">
                                <div style="font-weight: 600; margin-bottom: 4px; font-size: 14px;" x-text="product.name"></div>
                                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 4px;">
                                    <span x-text="currencySymbol + parseFloat(product.sale_price).toFixed(2)"></span>
                                </div>
                                <div style="font-size: 12px;">
                                    <span :style="'color: ' + (product.stock_quantity > 0 ? 'var(--success, #22c55e)' : 'var(--error)')">
                                        Stock: <span x-text="product.stock_quantity"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="card" style="position: sticky; top: 20px;">
            <div class="card-header">
                <h2 class="card-title">Cart (<span x-text="cart.length"></span>)</h2>
                <button type="button" class="btn btn-danger btn-sm" @click="clearCart()" x-show="cart.length > 0">Clear</button>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <template x-if="cart.length === 0">
                    <div class="empty-state">No items in cart</div>
                </template>
                <template x-for="(item, index) in cart" :key="item.product_id">
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 500; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="item.name"></div>
                            <div style="font-size: 12px; color: var(--text-secondary);" x-text="currencySymbol + parseFloat(item.price).toFixed(2) + ' each'"></div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                            <button type="button" class="btn btn-ghost btn-sm" @click="updateQuantity(index, -1)">-</button>
                            <input type="number" :value="item.quantity" @change="setQuantity(index, $event.target.value)" min="1" style="width: 45px; text-align: center;" class="form-input">
                            <button type="button" class="btn btn-ghost btn-sm" @click="updateQuantity(index, 1)">+</button>
                            <div style="min-width: 70px; text-align: right; font-weight: 600; font-size: 13px;" x-text="currencySymbol + (item.price * item.quantity).toFixed(2)"></div>
                            <button type="button" class="btn btn-ghost btn-sm" style="color: var(--error);" @click="removeFromCart(index)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div style="padding: 16px; border-top: 2px solid var(--border);" x-show="cart.length > 0">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span>Subtotal:</span>
                    <strong x-text="currencySymbol + subtotal.toFixed(2)"></strong>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="min-width: 70px;">Discount:</span>
                    <input type="number" x-model.number="discount" min="0" step="0.01" class="form-input" style="width: 100px;" placeholder="0.00">
                </div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="min-width: 70px;">Shipping:</span>
                    <input type="number" x-model.number="shipping" min="0" step="0.01" class="form-input" style="width: 100px;" placeholder="0.00">
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 16px; padding-top: 8px; border-top: 1px solid var(--border); font-size: 18px;">
                    <span>Total:</span>
                    <strong x-text="currencySymbol + grandTotal.toFixed(2)"></strong>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Customer</label>
                    <select x-model="customer_id" class="form-input">
                        <option value="">Walk-in Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->first_name }} {{ $customer->last_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Paid Amount *</label>
                    <input type="number" x-model.number="paidAmount" min="0" step="0.01" class="form-input" required>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 16px; padding: 8px 12px; background: var(--bg-secondary, #f9fafb); border-radius: 8px;">
                    <span>Due:</span>
                    <strong :style="'color: ' + (dueAmount > 0 ? 'var(--error)' : 'var(--success, #22c55e)')" x-text="currencySymbol + dueAmount.toFixed(2)"></strong>
                </div>

                <button type="button" class="btn btn-primary" style="width: 100%;" @click="checkout()" :disabled="submitting">
                    <span x-show="!submitting">Complete Sale</span>
                    <span x-show="submitting">Processing...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function posHandler() {
        return {
            products: {!! json_encode($products) !!},
            cart: [],
            searchQuery: '',
            customer_id: '',
            discount: 0,
            shipping: 0,
            paidAmount: 0,
            submitting: false,
            errorMessage: '',
            successMessage: '',
            currencySymbol: '{{ $currencySymbol }}',
            csrfToken: '',
            init() {
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            },
            get filteredProducts() {
                if (!this.searchQuery) return this.products.filter(p => p.stock_quantity > 0);
                const q = this.searchQuery.toLowerCase();
                return this.products.filter(p => p.stock_quantity > 0 && (p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q))));
            },
            get subtotal() {
                return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            },
            get grandTotal() {
                return Math.max(0, this.subtotal - this.discount + this.shipping);
            },
            get dueAmount() {
                return Math.max(0, this.grandTotal - this.paidAmount);
            },
            addToCart(product) {
                const existing = this.cart.find(i => i.product_id === product.id);
                if (existing) {
                    if (existing.quantity < product.stock_quantity) {
                        existing.quantity++;
                    }
                } else {
                    this.cart.push({
                        product_id: product.id,
                        name: product.name,
                        price: parseFloat(product.sale_price),
                        quantity: 1,
                        stock_quantity: product.stock_quantity,
                    });
                }
            },
            removeFromCart(index) {
                this.cart.splice(index, 1);
            },
            updateQuantity(index, delta) {
                const item = this.cart[index];
                const newQty = item.quantity + delta;
                if (newQty > 0 && newQty <= item.stock_quantity) {
                    item.quantity = newQty;
                }
            },
            setQuantity(index, value) {
                const qty = parseInt(value);
                const item = this.cart[index];
                if (qty > 0 && qty <= item.stock_quantity) {
                    item.quantity = qty;
                }
            },
            clearCart() {
                this.cart = [];
                this.discount = 0;
                this.shipping = 0;
                this.paidAmount = 0;
            },
            async checkout() {
                if (this.cart.length === 0) {
                    this.errorMessage = 'Cart is empty. Add items before checkout.';
                    return;
                }
                if (this.paidAmount <= 0) {
                    this.errorMessage = 'Please enter a paid amount.';
                    return;
                }
                this.submitting = true;
                this.errorMessage = '';
                this.successMessage = '';

                try {
                    const response = await fetch('{{ route("pos.checkout") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            cart: this.cart,
                            customer_id: this.customer_id,
                            discount: this.discount,
                            shipping: this.shipping,
                            paid_amount: this.paidAmount,
                        }),
                    });

                    const data = await response.json();

                    if (response.ok) {
                        window.location.href = data.redirect || '{{ route("pos.index") }}';
                        return;
                    }

                    this.errorMessage = data.message || 'Checkout failed. Please try again.';
                } catch (e) {
                    this.errorMessage = 'An unexpected error occurred. Please try again.';
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>
@endsection
