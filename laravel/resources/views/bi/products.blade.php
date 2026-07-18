<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Analytics — BI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        .bi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .bi-card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #e5e7eb; }
        .bi-card .label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
        .bi-card .value { font-size: 28px; font-weight: 700; color: #111827; }
        .data-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #e5e7eb; }
        .data-table th { background: #f9fafb; padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #e5e7eb; }
        .data-table td { padding: 12px 16px; font-size: 14px; color: #374151; border-bottom: 1px solid #f3f4f6; }
        .data-table tr:hover td { background: #f9fafb; }
        .tier-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .tier-high { background: #d1fae5; color: #065f46; }
        .tier-medium { background: #fef3c7; color: #92400e; }
        .tier-low { background: #fee2e2; color: #991b1b; }
        .charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        @media (max-width: 768px) { .charts-row { grid-template-columns: 1fr; } }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: #3b82f6; text-decoration: none; font-size: 14px; margin-bottom: 16px; }
    </style>
</head>
<body class="dashboard-body">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    @include('layouts.sidebar')
    <main class="main-content">
        <header class="dashboard-header">
            <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <h1 class="page-title">📦 Product Analytics</h1>
            @include('layouts.header-actions')
        </header>
        <div style="padding: 24px;">
            <a href="{{ route('bi.dashboard') }}" class="back-link">← Back to BI Dashboard</a>

            <div class="bi-grid">
                <div class="bi-card">
                    <div class="label">Total Products</div>
                    <div class="value">{{ $data['summary']['total_products'] ?? 0 }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Total Revenue</div>
                    <div class="value">${{ number_format($data['summary']['total_revenue'] ?? 0, 2) }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Top Product</div>
                    <div class="value" style="font-size:16px;">{{ $data['summary']['top_product'] ?? '-' }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Avg Score</div>
                    <div class="value">{{ $data['summary']['avg_score'] ?? 0 }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">High Tier</div>
                    <div class="value" style="color:#059669">{{ $data['summary']['high_tier'] ?? 0 }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Low Tier</div>
                    <div class="value" style="color:#dc2626">{{ $data['summary']['low_tier'] ?? 0 }}</div>
                </div>
            </div>

            <div class="charts-row">
                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #e5e7eb;">
                    <h3 style="font-size:15px;font-weight:600;color:#374151;margin-bottom:16px;">Performance Scores</h3>
                    <canvas id="scoreChart" height="200"></canvas>
                </div>
                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #e5e7eb;">
                    <h3 style="font-size:15px;font-weight:600;color:#374151;margin-bottom:16px;">Tier Distribution</h3>
                    <canvas id="tierChart" height="200"></canvas>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                        <th>Score</th>
                        <th>Tier</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($data['data'] ?? []) as $p)
                    <tr>
                        <td style="font-weight:600;">{{ $p['name'] ?? '-' }}</td>
                        <td>{{ $p['category'] ?? '-' }}</td>
                        <td>{{ $p['brand'] ?? '-' }}</td>
                        <td>${{ number_format($p['price'] ?? 0, 2) }}</td>
                        <td>{{ $p['stock'] ?? 0 }}</td>
                        <td>{{ $p['units_sold'] ?? 0 }}</td>
                        <td>${{ number_format($p['revenue'] ?? 0, 2) }}</td>
                        <td style="font-weight:600;">{{ $p['performance_score'] ?? 0 }}</td>
                        <td>
                            <span class="tier-badge tier-{{ strtolower($p['performance_tier'] ?? 'low') }}">{{ $p['performance_tier'] ?? '-' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:24px;">No product data found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
    @include('layouts.header-actions-js')
    <script>
        @php $productsJson = $data['data'] ?? []; @endphp
        const products = {!! json_encode($productsJson) !!};
        if (products.length) {
            new Chart(document.getElementById('scoreChart'), {
                type: 'bar',
                data: {
                    labels: products.map(p => p.name?.substring(0, 15)),
                    datasets: [{
                        label: 'Score',
                        data: products.map(p => p.performance_score),
                        backgroundColor: products.map(p =>
                            p.performance_tier === 'High' ? '#10b981' :
                            p.performance_tier === 'Medium' ? '#f59e0b' : '#ef4444'
                        ),
                        borderRadius: 6
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
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
    </script>
</body>
</html>
