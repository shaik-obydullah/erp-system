<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BI Dashboard — {{ config('app.name', 'ERP') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        .bi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .bi-card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid var(--border, #e5e7eb); }
        .bi-card .label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
        .bi-card .value { font-size: 28px; font-weight: 700; color: #111827; }
        .bi-card .sub { font-size: 12px; color: #9ca3af; margin-top: 4px; }
        .bi-chart-card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid var(--border, #e5e7eb); margin-bottom: 24px; }
        .bi-chart-card h3 { font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 16px; }
        .charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        @media (max-width: 768px) { .charts-row { grid-template-columns: 1fr; } }
        .tier-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .tier-high { background: #d1fae5; color: #065f46; }
        .tier-medium { background: #fef3c7; color: #92400e; }
        .tier-low { background: #fee2e2; color: #991b1b; }
        .nav-bi { background: #eff6ff; border-left: 3px solid #3b82f6; }
    </style>
    @yield('head')
</head>
<body class="dashboard-body">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    @include('layouts.sidebar')
    <main class="main-content">
        <header class="dashboard-header">
            <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <h1 class="page-title">📊 Business Intelligence</h1>
            @include('layouts.header-actions')
        </header>

        <div style="padding: 24px;">
            {{-- Summary KPIs --}}
            <div class="bi-grid">
                <div class="bi-card">
                    <div class="label">Total Products</div>
                    <div class="value">{{ $productData['summary']['total_products'] ?? 0 }}</div>
                    <div class="sub">Active in catalog</div>
                </div>
                <div class="bi-card">
                    <div class="label">Total Revenue</div>
                    <div class="value">${{ number_format($productData['summary']['total_revenue'] ?? 0, 2) }}</div>
                    <div class="sub">From {{ $productData['summary']['total_sold'] ?? 0 }} units sold</div>
                </div>
                <div class="bi-card">
                    <div class="label">Employees</div>
                    <div class="value">{{ $employeeData['summary']['total_employees'] ?? 0 }}</div>
                    <div class="sub">Avg score: {{ $employeeData['summary']['avg_score'] ?? 0 }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Sales Forecast</div>
                    <div class="value">${{ number_format($forecastData['data']['forecast']['next_month_forecast'] ?? 0, 2) }}</div>
                    <div class="sub">Next month estimate</div>
                </div>
                <div class="bi-card">
                    <div class="label">High-Tier Products</div>
                    <div class="value">{{ $productData['summary']['high_tier'] ?? 0 }}</div>
                    <div class="sub">Performance tier</div>
                </div>
                <div class="bi-card">
                    <div class="label">Anomalies</div>
                    <div class="value">{{ $employeeData['summary']['anomalies_detected'] ?? 0 }}</div>
                    <div class="sub">Employee anomalies</div>
                </div>
                <div class="bi-card">
                    <div class="label">Total Stock</div>
                    <div class="value">{{ number_format($productData['summary']['total_stock'] ?? 0) }}</div>
                    <div class="sub">Units across all products</div>
                </div>
                <div class="bi-card">
                    <div class="label">Product Combos</div>
                    <div class="value">{{ $comboData['data']['summary']['frequent_combos'] ?? 0 }}</div>
                    <div class="sub">Frequent itemsets found</div>
                </div>
            </div>

            {{-- Charts --}}
            <div class="charts-row">
                <div class="bi-chart-card">
                    <h3>Product Performance Scores</h3>
                    <canvas id="productChart" height="200"></canvas>
                </div>
                <div class="bi-chart-card">
                    <h3>Revenue by Product</h3>
                    <canvas id="revenueChart" height="200"></canvas>
                </div>
            </div>

            <div class="charts-row">
                <div class="bi-chart-card">
                    <h3>Product Tier Distribution</h3>
                    <canvas id="tierChart" height="200"></canvas>
                </div>
                <div class="bi-chart-card">
                    <h3>Employee Performance</h3>
                    <canvas id="empChart" height="200"></canvas>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="bi-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                <a href="{{ route('bi.employees') }}" class="bi-card" style="text-decoration:none;text-align:center;cursor:pointer;transition:transform .15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <div style="font-size:28px;margin-bottom:8px;">👥</div>
                    <div style="font-weight:600;color:#374151;">Employee Performance</div>
                </a>
                <a href="{{ route('bi.products') }}" class="bi-card" style="text-decoration:none;text-align:center;cursor:pointer;transition:transform .15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <div style="font-size:28px;margin-bottom:8px;">📦</div>
                    <div style="font-weight:600;color:#374151;">Product Analytics</div>
                </a>
                <a href="{{ route('bi.recommendations') }}" class="bi-card" style="text-decoration:none;text-align:center;cursor:pointer;transition:transform .15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <div style="font-size:28px;margin-bottom:8px;">🎯</div>
                    <div style="font-weight:600;color:#374151;">Recommendations</div>
                </a>
                <a href="{{ route('bi.forecast') }}" class="bi-card" style="text-decoration:none;text-align:center;cursor:pointer;transition:transform .15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <div style="font-size:28px;margin-bottom:8px;">📈</div>
                    <div style="font-weight:600;color:#374151;">Sales Forecast</div>
                </a>
                <a href="{{ route('bi.combos') }}" class="bi-card" style="text-decoration:none;text-align:center;cursor:pointer;transition:transform .15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <div style="font-size:28px;margin-bottom:8px;">🔗</div>
                    <div style="font-weight:600;color:#374151;">Product Combos</div>
                </a>
            </div>
        </div>
    </main>

    @include('layouts.header-actions-js')
    <script>
    @php
        $productsJson = $productData['data'] ?? [];
        $employeesJson = $employeeData['data'] ?? [];
        $forecastJson = $forecastData['data'] ?? [];
    @endphp
        const products = {!! json_encode($productsJson) !!};
        const employees = {!! json_encode($employeesJson) !!};
        const forecast = {!! json_encode($forecastJson) !!};

        // Product Performance Bar Chart
        if (products.length) {
            new Chart(document.getElementById('productChart'), {
                type: 'bar',
                data: {
                    labels: products.slice(0, 10).map(p => p.name?.substring(0, 15)),
                    datasets: [{
                        label: 'Score',
                        data: products.slice(0, 10).map(p => p.performance_score),
                        backgroundColor: products.slice(0, 10).map(p =>
                            p.performance_tier === 'High' ? '#10b981' :
                            p.performance_tier === 'Medium' ? '#f59e0b' : '#ef4444'
                        ),
                        borderRadius: 6
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });

            // Revenue Chart
            new Chart(document.getElementById('revenueChart'), {
                type: 'doughnut',
                data: {
                    labels: products.filter(p => p.revenue > 0).map(p => p.name?.substring(0, 15)),
                    datasets: [{
                        data: products.filter(p => p.revenue > 0).map(p => p.revenue),
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
                    }]
                },
                options: { responsive: true }
            });
        }

        // Tier Distribution Pie
        if (products.length) {
            const tiers = { High: 0, Medium: 0, Low: 0 };
            products.forEach(p => { if (tiers[p.performance_tier] !== undefined) tiers[p.performance_tier]++; });
            new Chart(document.getElementById('tierChart'), {
                type: 'pie',
                data: {
                    labels: Object.keys(tiers),
                    datasets: [{ data: Object.values(tiers), backgroundColor: ['#10b981', '#f59e0b', '#ef4444'] }]
                },
                options: { responsive: true }
            });
        }

        // Employee Performance Bar
        if (employees.length) {
            new Chart(document.getElementById('empChart'), {
                type: 'bar',
                data: {
                    labels: employees.map(e => e.name),
                    datasets: [{
                        label: 'Score',
                        data: employees.map(e => e.performance_score),
                        backgroundColor: employees.map(e =>
                            e.performance_tier === 'High' ? '#10b981' :
                            e.performance_tier === 'Medium' ? '#f59e0b' : '#ef4444'
                        ),
                        borderRadius: 6
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } }
            });
        }
    </script>
</body>
</html>
