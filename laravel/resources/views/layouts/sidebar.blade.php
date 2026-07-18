<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2 class="sidebar-logo">
            <span class="logo-erp">ERP</span><span class="logo-admin">Admin</span>
        </h2>
        <button class="sidebar-close" id="sidebarClose">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>
    </div>
    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
        </a>

        <!-- Administration Dropdown -->
        <div class="nav-dropdown" id="adminDropdown" x-data="{ open: {{ request()->routeIs('admins.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('activities.*') || request()->routeIs('notifications.*') || request()->routeIs('settings.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Administration
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('admins.index') }}" class="nav-dropdown-item {{ request()->routeIs('admins.*') ? 'active' : '' }}">Admins</a>
                <a href="{{ route('roles.index') }}" class="nav-dropdown-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">Roles</a>
                <a href="{{ route('permissions.index') }}" class="nav-dropdown-item {{ request()->routeIs('permissions.*') ? 'active' : '' }}">Permissions</a>
                <a href="{{ route('activities.index') }}" class="nav-dropdown-item {{ request()->routeIs('activities.*') ? 'active' : '' }}">Activity Log</a>
                <a href="{{ route('notifications.index') }}" class="nav-dropdown-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">Notifications</a>
                <a href="{{ route('settings.index') }}" class="nav-dropdown-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">ERP Settings</a>
            </div>
        </div>

        <!-- Customers Dropdown -->
        <div class="nav-dropdown" id="customersDropdown" x-data="{ open: {{ request()->routeIs('customers.*') || request()->routeIs('customers.fund.*') || request()->routeIs('customers.due.*') || request()->routeIs('customers.transaction.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Customers
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('customers.index') }}" class="nav-dropdown-item {{ request()->routeIs('customers.index') ? 'active' : '' }}">All Customers</a>
                <a href="{{ route('customers.create') }}" class="nav-dropdown-item {{ request()->routeIs('customers.create') ? 'active' : '' }}">Add Customer</a>
                <div style="border-top: 1px solid var(--border); margin: 4px 0;"></div>
                <a href="{{ route('customers.fund.index') }}" class="nav-dropdown-item {{ request()->routeIs('customers.fund.*') ? 'active' : '' }}">Customer Fund</a>
                <a href="{{ route('customers.due.index') }}" class="nav-dropdown-item {{ request()->routeIs('customers.due.*') ? 'active' : '' }}">Customer Due</a>
                <a href="{{ route('customers.transaction.index') }}" class="nav-dropdown-item {{ request()->routeIs('customers.transaction.*') ? 'active' : '' }}">Transactions</a>
            </div>
        </div>

        <!-- Suppliers Dropdown -->
        <div class="nav-dropdown" id="suppliersDropdown" x-data="{ open: {{ request()->routeIs('suppliers.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                Suppliers
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('suppliers.index') }}" class="nav-dropdown-item {{ request()->routeIs('suppliers.index') ? 'active' : '' }}">All Suppliers</a>
                <a href="{{ route('suppliers.create') }}" class="nav-dropdown-item {{ request()->routeIs('suppliers.create') ? 'active' : '' }}">Add Supplier</a>
                <div style="border-top: 1px solid var(--border); margin: 4px 0;"></div>
                <a href="{{ route('suppliers.fund.index') }}" class="nav-dropdown-item {{ request()->routeIs('suppliers.fund.*') ? 'active' : '' }}">Supplier Fund</a>
                <a href="{{ route('suppliers.due.index') }}" class="nav-dropdown-item {{ request()->routeIs('suppliers.due.*') ? 'active' : '' }}">Supplier Due</a>
                <a href="{{ route('suppliers.transaction.index') }}" class="nav-dropdown-item {{ request()->routeIs('suppliers.transaction.*') ? 'active' : '' }}">Transactions</a>
            </div>
        </div>

        <!-- Products & Catalog Dropdown -->
        <div class="nav-dropdown" id="productsDropdown" x-data="{ open: {{ request()->routeIs('products.*') || request()->routeIs('brands.*') || request()->routeIs('categories.*') || request()->routeIs('units.*') || request()->routeIs('sizes.*') || request()->routeIs('colors.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Products & Catalog
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('products.index') }}" class="nav-dropdown-item {{ request()->routeIs('products.index') ? 'active' : '' }}">All Products</a>
                <a href="{{ route('products.create') }}" class="nav-dropdown-item {{ request()->routeIs('products.create') ? 'active' : '' }}">Add Product</a>
                <a href="{{ route('products.barcodes') }}" class="nav-dropdown-item {{ request()->routeIs('products.barcodes') ? 'active' : '' }}">Barcodes</a>
                <div style="border-top: 1px solid var(--border); margin: 4px 0;"></div>
                <a href="{{ route('brands.index') }}" class="nav-dropdown-item {{ request()->routeIs('brands.*') ? 'active' : '' }}">Brands</a>
                <a href="{{ route('categories.index') }}" class="nav-dropdown-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">Categories</a>
                <a href="{{ route('units.index') }}" class="nav-dropdown-item {{ request()->routeIs('units.*') ? 'active' : '' }}">Units</a>
                <a href="{{ route('sizes.index') }}" class="nav-dropdown-item {{ request()->routeIs('sizes.*') ? 'active' : '' }}">Sizes</a>
                <a href="{{ route('colors.index') }}" class="nav-dropdown-item {{ request()->routeIs('colors.*') ? 'active' : '' }}">Colors</a>
            </div>
        </div>

        <!-- Stock & Inventory Dropdown -->
        <div class="nav-dropdown" x-data="{ open: {{ request()->routeIs('stocks.*') || request()->routeIs('stock-adjustments.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Stock & Inventory
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('stocks.index') }}" class="nav-dropdown-item {{ request()->routeIs('stocks.*') ? 'active' : '' }}">All Stocks</a>
                <a href="{{ route('stocks.create') }}" class="nav-dropdown-item {{ request()->routeIs('stocks.create') ? 'active' : '' }}">Add Stock</a>
                <div style="border-top: 1px solid var(--border); margin: 4px 0;"></div>
                <a href="{{ route('stock-adjustments.index') }}" class="nav-dropdown-item {{ request()->routeIs('stock-adjustments.*') ? 'active' : '' }}">Stock Adjustments</a>
            </div>
        </div>

        <!-- Sales & POS Dropdown -->
        <div class="nav-dropdown" x-data="{ open: {{ request()->routeIs('pos.*') || request()->routeIs('sales.*') || request()->routeIs('sale-returns.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Sales & POS
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('pos.index') }}" class="nav-dropdown-item {{ request()->routeIs('pos.*') ? 'active' : '' }}">POS Terminal</a>
                <a href="{{ route('sales.index') }}" class="nav-dropdown-item {{ request()->routeIs('sales.*') ? 'active' : '' }}">All Sales</a>
                <a href="{{ route('sale-returns.index') }}" class="nav-dropdown-item {{ request()->routeIs('sale-returns.*') ? 'active' : '' }}">Sale Returns</a>
            </div>
        </div>

        <!-- Procurement Dropdown -->
        <div class="nav-dropdown" x-data="{ open: {{ request()->routeIs('needs.*') || request()->routeIs('purchase-orders.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Procurement
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('needs.index') }}" class="nav-dropdown-item {{ request()->routeIs('needs.*') ? 'active' : '' }}">Procurement Needs</a>
                <a href="{{ route('purchase-orders.index') }}" class="nav-dropdown-item {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">Purchase Orders</a>
            </div>
        </div>

        <!-- Finance Dropdown -->
        <div class="nav-dropdown" x-data="{ open: {{ request()->routeIs('cashbook.*') || request()->routeIs('incomes.*') || request()->routeIs('expenses.*') || request()->routeIs('payables.*') || request()->routeIs('receivables.*') || request()->routeIs('fixed-assets.*') || request()->routeIs('transactions.*') || request()->routeIs('currencies.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Finance
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('cashbook.index') }}" class="nav-dropdown-item {{ request()->routeIs('cashbook.*') ? 'active' : '' }}">Cashbook</a>
                <a href="{{ route('incomes.index') }}" class="nav-dropdown-item {{ request()->routeIs('incomes.*') ? 'active' : '' }}">Income</a>
                <a href="{{ route('expenses.index') }}" class="nav-dropdown-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}">Expense</a>
                <a href="{{ route('payables.index') }}" class="nav-dropdown-item {{ request()->routeIs('payables.*') ? 'active' : '' }}">Accounts Payable</a>
                <a href="{{ route('receivables.index') }}" class="nav-dropdown-item {{ request()->routeIs('receivables.*') ? 'active' : '' }}">Accounts Receivable</a>
                <a href="{{ route('fixed-assets.index') }}" class="nav-dropdown-item {{ request()->routeIs('fixed-assets.*') ? 'active' : '' }}">Fixed Assets</a>
                <a href="{{ route('currencies.index') }}" class="nav-dropdown-item {{ request()->routeIs('currencies.*') ? 'active' : '' }}">Currencies</a>
                <a href="{{ route('transactions.index') }}" class="nav-dropdown-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">Transaction Log</a>
            </div>
        </div>

        <!-- HRM Dropdown -->
        <div class="nav-dropdown" x-data="{ open: {{ request()->routeIs('employees.*') || request()->routeIs('payrolls.*') || request()->routeIs('tasks.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                HRM
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('employees.index') }}" class="nav-dropdown-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">Employees</a>
                <a href="{{ route('payrolls.index') }}" class="nav-dropdown-item {{ request()->routeIs('payrolls.*') ? 'active' : '' }}">Payroll</a>
                <a href="{{ route('tasks.index') }}" class="nav-dropdown-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}">Task Management</a>
            </div>
        </div>

        <!-- Marketing & Logistics Dropdown -->
        <div class="nav-dropdown" x-data="{ open: {{ request()->routeIs('campaigns.*') || request()->routeIs('warehouses.*') || request()->routeIs('shipments.*') || request()->routeIs('shipment-returns.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                Marketing & Logistics
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('campaigns.index') }}" class="nav-dropdown-item {{ request()->routeIs('campaigns.*') ? 'active' : '' }}">Campaigns</a>
                <div style="border-top: 1px solid var(--border); margin: 4px 0;"></div>
                <a href="{{ route('warehouses.index') }}" class="nav-dropdown-item {{ request()->routeIs('warehouses.*') ? 'active' : '' }}">Warehouses</a>
                <a href="{{ route('shipments.index') }}" class="nav-dropdown-item {{ request()->routeIs('shipments.*') ? 'active' : '' }}">Shipments</a>
                <a href="{{ route('shipment-returns.index') }}" class="nav-dropdown-item {{ request()->routeIs('shipment-returns.*') ? 'active' : '' }}">Shipment Returns</a>
            </div>
        </div>

        <!-- Manufacturing Dropdown -->
        <div class="nav-dropdown" x-data="{ open: {{ request()->routeIs('bill-of-materials.*') || request()->routeIs('production-plannings.*') || request()->routeIs('productions.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 20h20"/><path d="M5 20V8l7-5 7 5v12"/><rect x="9" y="12" width="6" height="8"/></svg>
                Manufacturing
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('bill-of-materials.index') }}" class="nav-dropdown-item {{ request()->routeIs('bill-of-materials.*') ? 'active' : '' }}">Bill of Materials</a>
                <a href="{{ route('production-plannings.index') }}" class="nav-dropdown-item {{ request()->routeIs('production-plannings.*') ? 'active' : '' }}">Production Planning</a>
                <a href="{{ route('productions.index') }}" class="nav-dropdown-item {{ request()->routeIs('productions.*') ? 'active' : '' }}">Production</a>
            </div>
        </div>

        <!-- CMS Dropdown -->
        <div class="nav-dropdown" x-data="{ open: {{ request()->routeIs('cms.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                CMS
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('cms.index') }}" class="nav-dropdown-item {{ request()->routeIs('cms.*') ? 'active' : '' }}">All Content</a>
                <a href="{{ route('cms.create') }}" class="nav-dropdown-item {{ request()->routeIs('cms.create') ? 'active' : '' }}">Add Content</a>
            </div>
        </div>

        <!-- Reports Dropdown -->
        <div class="nav-dropdown" x-data="{ open: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }" :class="{ 'open': open }">
            <button class="nav-item nav-dropdown-toggle" @click="open = !open">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                Reports
                <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="nav-dropdown-menu">
                <a href="{{ route('reports.index') }}" class="nav-dropdown-item {{ request()->routeIs('reports.index') ? 'active' : '' }}">Overview</a>
                <a href="{{ route('reports.sales') }}" class="nav-dropdown-item {{ request()->routeIs('reports.sales') ? 'active' : '' }}">Sales Report</a>
                <a href="{{ route('reports.income') }}" class="nav-dropdown-item {{ request()->routeIs('reports.income') ? 'active' : '' }}">Income Report</a>
                <a href="{{ route('reports.expense') }}" class="nav-dropdown-item {{ request()->routeIs('reports.expense') ? 'active' : '' }}">Expense Report</a>
                <a href="{{ route('reports.stock') }}" class="nav-dropdown-item {{ request()->routeIs('reports.stock') ? 'active' : '' }}">Stock Report</a>
                <a href="{{ route('reports.customers') }}" class="nav-dropdown-item {{ request()->routeIs('reports.customers') ? 'active' : '' }}">Customer Report</a>
                <a href="{{ route('reports.suppliers') }}" class="nav-dropdown-item {{ request()->routeIs('reports.suppliers') ? 'active' : '' }}">Supplier Report</a>
            </div>
        </div>
    </nav>
</aside>
