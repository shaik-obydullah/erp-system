@extends('roles.layout')

@section('title', 'New Stock Adjustment')

@push('head')
@include('layouts.partials.form-styles')
@endpush

@section('content')
<div class="card form-card" x-data="formHandler('{{ route('stock-adjustments.store') }}', 'POST')" x-init="init()">
    <div class="card-header">
        <div>
            <h2 class="card-title">New Stock Adjustment</h2>
            <p class="card-subtitle">Correct stock counts for damage, corrections or returns.</p>
        </div>
        <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">Back to Adjustments</a>
    </div>
    <div class="card-body">

        <div x-show="successMessage" x-cloak class="alert alert-success show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span x-text="successMessage"></span>
        </div>

        <div x-show="errorMessage" x-cloak class="alert alert-error show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <span x-text="errorMessage"></span>
        </div>

        <form @submit.prevent="submit($event)">
            @csrf

            <section class="form-section">
                <div class="section-heading">
                    <div class="section-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </div>
                    <div>
                        <h3 class="section-title">Stock to adjust</h3>
                        <p class="section-hint">Find the stock batch you want to correct, then confirm its warehouse and batch details.</p>
                    </div>
                </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="stock_search">Stock <span class="required">*</span></label>
                    <div class="searchable-select" @click.outside="stockOpen = false">
                        <input type="text" id="stock_search" x-model="stockSearch"
                            @focus="$event.target.select(); stockOpen = true"
                            @input="stockOpen = true; form.fk_stock_id = ''"
                            @keydown.escape="stockOpen = false"
                            @keydown.enter="if (stockOpen && !form.fk_stock_id && filteredStocks.length) { $event.preventDefault(); selectStock(filteredStocks[0]); }"
                            placeholder="Search stock by product, SKU or batch..."
                            autocomplete="off"
                            role="combobox"
                            aria-autocomplete="list"
                            aria-controls="stock-options"
                            :aria-expanded="stockOpen">
                        <svg class="select-chevron" x-show="!form.fk_stock_id" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        <button type="button" class="clear-btn" x-show="form.fk_stock_id" @click="clearStock()" aria-label="Clear selected stock">&times;</button>
                        <div class="searchable-options" id="stock-options" x-show="stockOpen" x-cloak>
                            <template x-for="stock in filteredStocks" :key="stock.id">
                                <button type="button" class="searchable-option"
                                    :class="{ 'is-selected': String(form.fk_stock_id) === String(stock.id) }"
                                    @click="selectStock(stock)">
                                    <span class="option-name" x-text="stock.name"></span>
                                    <span class="option-meta" x-text="stockMeta(stock)"></span>
                                </button>
                            </template>
                            <div class="searchable-empty" x-show="filteredStocks.length === 0">No stock matches your search.</div>
                        </div>
                    </div>
                    <span class="form-hint">Type to search by product name, SKU, batch or stock ID.</span>
                    <span class="form-error" x-show="errors.fk_stock_id" x-text="errors.fk_stock_id"></span>
                </div>
                <div class="form-group">
                    <label for="fk_warehouse_id">Warehouse</label>
                    <select id="fk_warehouse_id" x-model="form.fk_warehouse_id">
                        <option value="">Select Warehouse</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_warehouse_id" x-text="errors.fk_warehouse_id"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="batch">Batch</label>
                    <input type="text" id="batch" x-model="form.batch" placeholder="e.g. BATCH-2026-01">
                    <span class="form-hint">Optional reference used to trace this stock batch.</span>
                    <span class="form-error" x-show="errors.batch" x-text="errors.batch"></span>
                </div>
                <div class="form-group">
                    <label for="lot">Lot</label>
                    <input type="text" id="lot" x-model="form.lot" placeholder="e.g. LOT-4821">
                    <span class="form-hint">Optional lot number from the supplier or production run.</span>
                    <span class="form-error" x-show="errors.lot" x-text="errors.lot"></span>
                </div>
            </div>
            </section>

            <section class="form-section">
                <div class="section-heading">
                    <div class="section-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                    </div>
                    <div>
                        <h3 class="section-title">Adjustment details</h3>
                        <p class="section-hint">Enter how many units to adjust and why, so the change is traceable.</p>
                    </div>
                </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantity">Quantity <span class="required">*</span></label>
                    <input type="number" id="quantity" x-model="form.quantity" min="1" placeholder="e.g. 5" required>
                    <span class="form-hint">Number of units to remove from or add back to this stock.</span>
                    <span class="form-error" x-show="errors.quantity" x-text="errors.quantity"></span>
                </div>
                <div class="form-group">
                    <label for="reason">Reason <span class="required">*</span></label>
                    <select id="reason" x-model="form.reason" required>
                        <option value="">Select Reason</option>
                        <option value="correction">Correction (reduce stock)</option>
                        <option value="damage">Damage (reduce stock, record loss)</option>
                        <option value="return">Return (add stock back)</option>
                    </select>
                    <span class="form-hint">Damage and correction record an inventory loss; returns reverse that loss.</span>
                    <span class="form-error" x-show="errors.reason" x-text="errors.reason"></span>
                </div>
            </div>

            <div class="impact-preview" x-show="impactReady" x-cloak>
                <div class="impact-header">
                    <span class="impact-title">Accounting &amp; finance impact</span>
                    <span class="impact-badge" :class="isReturn ? 'is-in' : 'is-out'"
                        x-text="isReturn ? 'Stock in' : 'Stock out'"></span>
                </div>
                <div class="impact-grid">
                    <div>
                        <span class="impact-label">Adjustment value (at cost)</span>
                        <span class="impact-value" x-text="formatMoney(adjustmentCost)"></span>
                    </div>
                    <div>
                        <span class="impact-label">Projected stock quantity</span>
                        <span class="impact-value" x-text="projectedQuantity ?? '—'"></span>
                    </div>
                    <div>
                        <span class="impact-label">Debit account</span>
                        <span class="impact-value" x-text="debitAccount"></span>
                    </div>
                    <div>
                        <span class="impact-label">Credit account</span>
                        <span class="impact-value" x-text="creditAccount"></span>
                    </div>
                </div>
                <p class="impact-hint" x-show="willPostEntry">
                    <span x-text="isReturn
                        ? 'A non-cash income record will appear in Finance → Income.'
                        : 'A non-cash expense record will appear in Finance → Expense.'"></span>
                    No cashbook, bank or cash-flow movement.
                </p>
                <p class="impact-hint" x-show="!willPostEntry">
                    This stock has no cost value, so no journal or finance entry will be posted.
                </p>
            </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Create Adjustment</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                fk_stock_id: '',
                fk_warehouse_id: '',
                batch: '',
                lot: '',
                quantity: '',
                reason: '',
            },
            stocks: @json($stockOptions),
            currencySymbol: @json($currencySymbol),
            stockSearch: '',
            stockOpen: false,
            get filteredStocks() {
                const query = this.stockSearch.trim().toLowerCase();
                const selected = this.selectedStock;
                const isSelectedLabel = selected && this.stockSearch === this.stockLabel(selected);
                const matches = (query && !isSelectedLabel)
                    ? this.stocks.filter((stock) => `${stock.name} ${stock.sku || ''} ${stock.batch || ''} ${stock.lot || ''} ${stock.id}`.toLowerCase().includes(query))
                    : this.stocks;

                return matches.slice(0, 50);
            },
            get selectedStock() {
                return this.stocks.find((stock) => String(stock.id) === String(this.form.fk_stock_id)) || null;
            },
            get adjustmentQuantity() {
                const quantity = parseInt(this.form.quantity, 10);

                return Number.isFinite(quantity) && quantity > 0 ? quantity : 0;
            },
            get adjustmentCost() {
                return this.selectedStock
                    ? this.adjustmentQuantity * Number(this.selectedStock.buy_price || 0)
                    : 0;
            },
            get projectedQuantity() {
                const stock = this.selectedStock;

                if (!stock || !this.adjustmentQuantity) {
                    return null;
                }

                return this.isReturn
                    ? Number(stock.quantity) + this.adjustmentQuantity
                    : Math.max(0, Number(stock.quantity) - this.adjustmentQuantity);
            },
            get isReturn() {
                return this.form.reason === 'return';
            },
            get impactReady() {
                return !!this.selectedStock && !!this.form.reason && this.adjustmentQuantity > 0;
            },
            get willPostEntry() {
                return this.adjustmentCost > 0;
            },
            get debitAccount() {
                return this.isReturn ? 'Inventory (1200)' : 'Inventory adjustments / losses (5500)';
            },
            get creditAccount() {
                return this.isReturn ? 'Inventory adjustments / losses (5500)' : 'Inventory (1200)';
            },
            formatMoney(value) {
                return `${this.currencySymbol}${Number(value || 0).toFixed(2)}`;
            },
            stockMeta(stock) {
                return `Qty: ${stock.quantity}`
                    + (stock.batch ? ` • Batch: ${stock.batch}` : '')
                    + (stock.sku ? ` • SKU: ${stock.sku}` : '')
                    + (Number(stock.buy_price) > 0 ? ` • Cost: ${this.formatMoney(stock.buy_price)}` : '');
            },
            stockLabel(stock) {
                return `${stock.name} — Qty: ${stock.quantity}`
                    + (stock.batch ? ` (${stock.batch})` : '');
            },
            selectStock(stock) {
                this.form.fk_stock_id = String(stock.id);
                this.stockSearch = this.stockLabel(stock);
                this.stockOpen = false;
                delete this.errors.fk_stock_id;
            },
            clearStock() {
                this.form.fk_stock_id = '';
                this.stockSearch = '';
                this.stockOpen = true;
            },
            errors: {},
            errorMessage: '',
            successMessage: '',
            submitting: false,
            csrfToken: '',
            init() {
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const selected = this.selectedStock;
                this.stockSearch = selected ? this.stockLabel(selected) : '';
            },
            async submit() {
                this.errors = {};
                this.errorMessage = '';
                this.successMessage = '';

                if (!this.form.fk_stock_id) {
                    this.errors.fk_stock_id = 'Please select a stock item from the list.';
                    return;
                }

                this.submitting = true;

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.form),
                    });

                    const data = await response.json();

                    if (response.ok) {
                        window.location.href = data.redirect || '{{ route("stock-adjustments.index") }}';
                        return;
                    }

                    if (data.errors) {
                        this.errors = {};
                        for (const key in data.errors) {
                            this.errors[key] = data.errors[key][0];
                        }
                    } else if (data.message) {
                        this.errorMessage = data.message;
                    }
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
