document.addEventListener('alpine:init', () => {

    // Customer Filters Component
    Alpine.data('customerFilters', () => ({
        search: '',
        status: '',

        init() {
            this.search = this.$el.dataset.search || '';
            this.status = this.$el.dataset.status || '';
        },

        clear() {
            window.location.href = this.$el.dataset.clearUrl;
        },
    }));

    // Fund Manager Component
    Alpine.data('fundManager', () => ({
        showModal: false,
        selectedCustomer: '',
        currentBalance: 0,
        amount: '',

        async fetchBalance() {
            if (!this.selectedCustomer) {
                this.currentBalance = 0;
                return;
            }
            try {
                const res = await fetch(`/customers/fund/balance?customer_id=${this.selectedCustomer}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.currentBalance = data.balance;
            } catch (e) {
                this.currentBalance = 0;
            }
        },

        get newBalance() {
            return (parseFloat(this.currentBalance) + parseFloat(this.amount || 0)).toFixed(2);
        },

        openModal() {
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.selectedCustomer = '';
            this.currentBalance = 0;
            this.amount = '';
        },
    }));

    // Supplier Fund Manager Component
    Alpine.data('supplierFundManager', () => ({
        showModal: false,
        selectedSupplier: '',
        currentBalance: 0,
        amount: '',

        async fetchBalance() {
            if (!this.selectedSupplier) {
                this.currentBalance = 0;
                return;
            }
            try {
                const res = await fetch(`/suppliers/fund/balance?supplier_id=${this.selectedSupplier}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.currentBalance = data.balance;
            } catch (e) {
                this.currentBalance = 0;
            }
        },

        get newBalance() {
            return (parseFloat(this.currentBalance) + parseFloat(this.amount || 0)).toFixed(2);
        },

        openModal() {
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.selectedSupplier = '';
            this.currentBalance = 0;
            this.amount = '';
        },
    }));

    // Fund Filters Component
    Alpine.data('fundFilters', () => ({
        customerId: '',
        dateFrom: '',
        dateTo: '',

        init() {
            this.customerId = this.$el.dataset.customerId || '';
            this.dateFrom = this.$el.dataset.dateFrom || '';
            this.dateTo = this.$el.dataset.dateTo || '';
        },

        clear() {
            window.location.href = this.$el.dataset.clearUrl;
        },
    }));

    // Due Manager Component
    Alpine.data('dueManager', () => ({
        search: '',

        init() {
            this.search = this.$el.dataset.search || '';
        },

        clear() {
            window.location.href = this.$el.dataset.clearUrl;
        },
    }));

    // Transaction Filters Component
    Alpine.data('transactionFilters', () => ({
        customerId: '',
        dateFrom: '',
        dateTo: '',

        init() {
            this.customerId = this.$el.dataset.customerId || '';
            this.dateFrom = this.$el.dataset.dateFrom || '';
            this.dateTo = this.$el.dataset.dateTo || '';
        },

        clear() {
            window.location.href = this.$el.dataset.clearUrl;
        },
    }));

    // Delete Handler Component
    Alpine.data('deleteHandler', () => ({
        successMessage: '',
        csrfToken: '',
        showModal: false,
        deleteId: null,
        deleteUrl: '',
        deleteLabel: '',

        init() {
            this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        },

        confirmDelete(id, url, label) {
            this.deleteId = id;
            this.deleteUrl = url;
            this.deleteLabel = label;
            this.showModal = true;
        },

        cancelDelete() {
            this.showModal = false;
            this.deleteId = null;
            this.deleteUrl = '';
            this.deleteLabel = '';
        },

        async executeDelete() {
            try {
                const response = await fetch(this.deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                if (response.ok) {
                    const row = document.getElementById('row-' + this.deleteId);
                    if (row) row.remove();
                    this.successMessage = data.message;
                    setTimeout(() => { this.successMessage = ''; }, 3000);
                }
            } catch (e) {
                alert('An unexpected error occurred.');
            }

            this.showModal = false;
            this.deleteId = null;
            this.deleteUrl = '';
            this.deleteLabel = '';
        },
    }));

    // Auto-dismiss Flash Messages
    Alpine.data('flashMessage', () => ({
        show: true,

        init() {
            setTimeout(() => { this.show = false; }, 3000);
        },
    }));

});
