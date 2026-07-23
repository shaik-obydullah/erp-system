<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'ERP Admin') }} - Dashboard</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link rel="stylesheet" href="/css/app.css?v={{ md5_file(public_path('css/app.css')) }}">
        <script src="/js/app.js?v={{ md5_file(public_path('js/app.js')) }}"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body class="dashboard-body">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        @include('layouts.sidebar')

        <main class="main-content">
            <header class="dashboard-header">
                <button class="menu-toggle" id="menuToggle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <h1 class="page-title">Dashboard</h1>
                @if(session('success') || session('error'))
                <div class="header-flash" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-error">{{ session('error') }}</div>
                    @endif
                </div>
                @endif
                @include('layouts.header-actions')
            </header>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Total Sales</span>
                        <span class="stat-value">{{ $currencySymbol }}{{ number_format($totalSales, 0) }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Revenue Collected</span>
                        <span class="stat-value">{{ $currencySymbol }}{{ number_format($totalRevenue, 0) }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Customers</span>
                        <span class="stat-value">{{ number_format($totalCustomers) }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Products</span>
                        <span class="stat-value">{{ number_format($totalProducts) }}</span>
                    </div>
                </div>
            </div>

            <!-- Second Row Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fef3c7; color: #d97706;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Total Orders</span>
                        <span class="stat-value">{{ number_format($totalOrders) }}</span>
                        <span class="stat-change" style="color: var(--text-secondary);">{{ $completedOrders }} completed, {{ $pendingOrders }} pending</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fee2e2; color: #dc2626;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Total Due</span>
                        <span class="stat-value">{{ $currencySymbol }}{{ number_format($totalDue, 0) }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Stock Value</span>
                        <span class="stat-value">{{ $currencySymbol }}{{ number_format($totalStockValue, 0) }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #ede9fe; color: #7c3aed;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Suppliers</span>
                        <span class="stat-value">{{ number_format($totalSuppliers) }}</span>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Sales vs Expenses ({{ date('Y') }})</h3>
                    </div>
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Revenue by Category</h3>
                    </div>
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- Tables -->
            <div class="tables-grid">
                <div class="table-card">
                    <div class="table-header">
                        <h3 class="table-title">Top Products</h3>
                        <a href="{{ route('products.index') }}" class="view-all">View all</a>
                    </div>
                    <table class="data-table">
                        <thead><tr><th>Product</th><th>Sales</th><th>Stock</th></tr></thead>
                        <tbody>
                            @forelse($topProducts as $product)
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <div class="product-img blue"></div>
                                        <span>{{ $product->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $product->total_sold ?? $product->stocks_count ?? 0 }}</td>
                                <td>{{ $product->stock_quantity }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" style="text-align: center; color: var(--text-secondary); padding: 24px;">No products yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="table-card">
                    <div class="table-header">
                        <h3 class="table-title">Recent Orders</h3>
                        <a href="{{ route('sales.index') }}" class="view-all">View all</a>
                    </div>
                    <table class="data-table">
                        <thead><tr><th>Invoice</th><th>Type</th><th>Status</th><th>Total</th></tr></thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                            <tr>
                                <td><a href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_id ?? '#' . $sale->id }}</a></td>
                                <td>{{ $sale->type }}</td>
                                <td>
                                    <span class="status-badge {{ $sale->status === 'completed' ? 'completed' : ($sale->status === 'processing' ? 'processing' : 'pending') }}">
                                        {{ ucfirst($sale->status) }}
                                    </span>
                                </td>
                                <td>{{ $currencySymbol }}{{ number_format($sale->grand_total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 24px;">No orders yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        @include('layouts.header-actions-js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sidebar = document.getElementById('sidebar');
                const menuToggle = document.getElementById('menuToggle');
                const sidebarClose = document.getElementById('sidebarClose');
                const sidebarOverlay = document.getElementById('sidebarOverlay');

                menuToggle.addEventListener('click', () => { sidebar.classList.add('open'); sidebarOverlay.classList.add('show'); });
                if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
                if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
                function closeSidebar() { sidebar.classList.remove('open'); sidebarOverlay.classList.remove('show'); }

                // Sales vs Expenses Chart
                var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                var salesData = months.map((_, i) => {{ $currencySymbol === '$' ? '' : '' }}0);
                var expenseData = months.map(() => 0);

                @foreach($monthlySales as $month => $total)
                    salesData[{{ $month - 1 }}] = {{ $total }};
                @endforeach
                @foreach($monthlyExpenses as $month => $total)
                    expenseData[{{ $month - 1 }}] = {{ $total }};
                @endforeach

                new Chart(document.getElementById('salesChart').getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [{
                            label: 'Sales', data: salesData,
                            borderColor: '#1a73e8', backgroundColor: 'rgba(26,115,232,0.1)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 4, pointBackgroundColor: '#1a73e8'
                        }, {
                            label: 'Expenses', data: expenseData,
                            borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.05)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 4, pointBackgroundColor: '#dc2626'
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 12 } } } },
                        scales: { y: { beginAtZero: true, grid: { color: '#f1f3f4' }, ticks: { callback: v => '{{ $currencySymbol }}'+v.toLocaleString(), font: { size: 12 }, color: '#5f6368' } }, x: { grid: { display: false }, ticks: { font: { size: 12 }, color: '#5f6368' } } }
                    }
                });

                // Category Chart
                var catLabels = @json($categoryRevenue->pluck('category_name')->map(fn($n) => $n ?? 'Uncategorized')->toArray());
                var catData = @json($categoryRevenue->pluck('revenue')->toArray());
                var catColors = ['#1a73e8','#1e8e3e','#9334e6','#e37400','#dadce0'];

                if (catLabels.length === 0) {
                    catLabels = ['No Data'];
                    catData = [1];
                    catColors = ['#dadce0'];
                }

                new Chart(document.getElementById('categoryChart').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: catLabels,
                        datasets: [{ data: catData, backgroundColor: catColors, borderWidth: 0, hoverOffset: 8 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: '65%',
                        plugins: { legend: { position: 'right', labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 12 } } } }
                    }
                });
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var sidebar = document.getElementById('sidebar');
                var overlay = document.getElementById('sidebarOverlay');
                var menuToggle = document.getElementById('menuToggle');
                var sidebarClose = document.getElementById('sidebarClose');

                function openSidebar() {
                    sidebar.classList.add('open');
                    overlay.classList.add('show');
                    document.body.style.overflow = 'hidden';
                }

                function closeSidebar() {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                }

                if (menuToggle) menuToggle.addEventListener('click', openSidebar);
                if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
                if (overlay) overlay.addEventListener('click', closeSidebar);
            });
        </script>
    </body>
</html>
