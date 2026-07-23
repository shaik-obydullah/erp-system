<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Forecast — BI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/css/app.css?v={{ md5_file(public_path('css/app.css')) }}">
    <script src="/js/app.js?v={{ md5_file(public_path('js/app.js')) }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        .bi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .bi-card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #e5e7eb; }
        .bi-card .label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
        .bi-card .value { font-size: 28px; font-weight: 700; color: #111827; }
        .bi-card .sub { font-size: 12px; color: #9ca3af; margin-top: 4px; }
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
            <h1 class="page-title">📈 Sales Forecast</h1>
            @include('layouts.header-actions')
        </header>
        <div style="padding: 24px;">
            <a href="{{ route('bi.dashboard') }}" class="back-link">← Back to BI Dashboard</a>

            <div class="bi-grid">
                <div class="bi-card">
                    <div class="label">Next Month Forecast</div>
                    <div class="value">${{ number_format($data['data']['forecast']['next_month_forecast'] ?? 0, 2) }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Confidence</div>
                    <div class="value">{{ $data['data']['forecast']['confidence_level'] ?? 0 }}%</div>
                </div>
                <div class="bi-card">
                    <div class="label">Growth Rate</div>
                    <div class="value" style="color: {{ ($data['data']['forecast']['growth_rate'] ?? 0) >= 0 ? '#059669' : '#dc2626' }}">
                        {{ ($data['data']['forecast']['growth_rate'] ?? 0) >= 0 ? '+' : '' }}{{ $data['data']['forecast']['growth_rate'] ?? 0 }}%
                    </div>
                </div>
                <div class="bi-card">
                    <div class="label">Model Accuracy</div>
                    <div class="value">{{ $data['data']['forecast']['model_accuracy'] ?? 0 }}%</div>
                </div>
                <div class="bi-card">
                    <div class="label">Expected Range</div>
                    <div class="value" style="font-size:16px;">
                        ${{ number_format($data['data']['forecast']['expected_range_low'] ?? 0, 0) }}
                        — ${{ number_format($data['data']['forecast']['expected_range_high'] ?? 0, 0) }}
                    </div>
                </div>
                <div class="bi-card">
                    <div class="label">Total Revenue</div>
                    <div class="value">${{ number_format($data['summary']['total_revenue'] ?? 0, 2) }}</div>
                    <div class="sub">Avg ${{ number_format($data['summary']['avg_monthly'] ?? 0, 2) }}/mo</div>
                </div>
            </div>

            <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #e5e7eb;margin-bottom:24px;">
                <h3 style="font-size:15px;font-weight:600;color:#374151;margin-bottom:16px;">Monthly Sales Trend</h3>
                <canvas id="forecastChart" height="120"></canvas>
            </div>
        </div>
    </main>
    @include('layouts.header-actions-js')
    <script>
        @php
            $salesData = $data['data']['actual_sales'] ?? [];
            $forecastData = $data['data']['forecast'] ?? [];
        @endphp
        const sales = {!! json_encode($salesData) !!};
        const forecast = {!! json_encode($forecastData) !!};

        if (sales.length) {
            const labels = sales.map(s => s.month || s.period);
            const values = sales.map(s => s.total_sales || s.amount || 0);

            const datasets = [{
                label: 'Actual Sales',
                data: values,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: '#3b82f6'
            }];

            if (forecast.next_month_forecast > 0) {
                labels.push('Forecast');
                values.push(forecast.next_month_forecast);
                datasets[0].borderDash = undefined;
                datasets.push({
                    label: 'Forecast',
                    data: Array(values.length - 1).fill(null).concat([forecast.next_month_forecast]),
                    borderColor: '#f59e0b',
                    borderDash: [5, 5],
                    pointRadius: 6,
                    pointBackgroundColor: '#f59e0b',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    fill: false
                });
            }

            new Chart(document.getElementById('forecastChart'), {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString() } } }
                }
            });
        }
    </script>
</body>
</html>
