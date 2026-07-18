<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prophet Forecast — BI</title>
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
        .bi-card .sub { font-size: 12px; color: #9ca3af; margin-top: 4px; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: #3b82f6; text-decoration: none; font-size: 14px; margin-bottom: 16px; }
        .period-selector { display: flex; gap: 8px; margin-bottom: 20px; }
        .period-btn { padding: 6px 16px; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #374151; font-size: 13px; cursor: pointer; text-decoration: none; }
        .period-btn.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
        .period-btn:hover { background: #f3f4f6; }
        .period-btn.active:hover { background: #2563eb; }
        .error-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 20px; color: #991b1b; }
        .empty-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 40px; text-align: center; color: #6b7280; }
        .tag { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
        .tag-blue { background: #dbeafe; color: #1e40af; }
        .tag-green { background: #d1fae5; color: #065f46; }
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
            <h1 class="page-title">📊 Prophet Sales Forecast</h1>
            @include('layouts.header-actions')
        </header>
        <div style="padding: 24px;">
            <a href="{{ route('bi.dashboard') }}" class="back-link">← Back to BI Dashboard</a>

            <div class="period-selector">
                <a href="{{ route('bi.prophet-forecast', ['periods' => 6]) }}" class="period-btn {{ $periods == 6 ? 'active' : '' }}">6 Months</a>
                <a href="{{ route('bi.prophet-forecast', ['periods' => 12]) }}" class="period-btn {{ $periods == 12 ? 'active' : '' }}">12 Months</a>
                <a href="{{ route('bi.prophet-forecast', ['periods' => 24]) }}" class="period-btn {{ $periods == 24 ? 'active' : '' }}">24 Months</a>
                <a href="{{ route('bi.prophet-forecast', ['periods' => 36]) }}" class="period-btn {{ $periods == 36 ? 'active' : '' }}">36 Months</a>
            </div>

            @if(($data['status'] ?? '') === 'error')
                <div class="error-box">
                    <strong>Error:</strong> {{ $data['message'] ?? 'Unknown error' }}
                </div>
            @elseif(empty($data['data']))
                <div class="empty-box">
                    <p>{{ $data['message'] ?? 'No data available for forecasting.' }}</p>
                </div>
            @else
                @php $s = $data['data']['summary'] ?? []; @endphp
                <div class="bi-grid">
                    <div class="bi-card">
                        <div class="label">Avg Monthly Revenue</div>
                        <div class="value">${{ number_format($s['avg_monthly_revenue'] ?? 0, 2) }}</div>
                        <div class="sub">Historical average</div>
                    </div>
                    <div class="bi-card">
                        <div class="label">Last Month</div>
                        <div class="value">${{ number_format($s['last_month_revenue'] ?? 0, 2) }}</div>
                        <div class="sub">Most recent</div>
                    </div>
                    <div class="bi-card">
                        <div class="label">Avg Forecast</div>
                        <div class="value" style="color:#f59e0b;">${{ number_format($s['avg_forecast'] ?? 0, 2) }}</div>
                        <div class="sub">Next {{ $s['forecast_months'] ?? 0 }} months</div>
                    </div>
                    <div class="bi-card">
                        <div class="label">Peak Forecast</div>
                        <div class="value" style="color:#059669;">${{ number_format($s['peak_forecast'] ?? 0, 2) }}</div>
                        <div class="sub">{{ $s['peak_month'] ?? 'N/A' }}</div>
                    </div>
                    <div class="bi-card">
                        <div class="label">Current Growth</div>
                        <div class="value" style="color: {{ ($s['current_growth_rate'] ?? 0) >= 0 ? '#059669' : '#dc2626' }}">
                            {{ ($s['current_growth_rate'] ?? 0) >= 0 ? '+' : '' }}{{ $s['current_growth_rate'] ?? 0 }}%
                        </div>
                        <div class="sub">Month-over-month</div>
                    </div>
                    <div class="bi-card">
                        <div class="label">Forecast Growth</div>
                        <div class="value" style="color: {{ ($s['forecast_growth_rate'] ?? 0) >= 0 ? '#059669' : '#dc2626' }}">
                            {{ ($s['forecast_growth_rate'] ?? 0) >= 0 ? '+' : '' }}{{ $s['forecast_growth_rate'] ?? 0 }}%
                        </div>
                        <div class="sub">Predicted</div>
                    </div>
                </div>

                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #e5e7eb;margin-bottom:24px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <h3 style="font-size:15px;font-weight:600;color:#374151;">Sales Trend with Prophet Forecast</h3>
                        <div style="display:flex;gap:12px;font-size:12px;">
                            <span><span style="display:inline-block;width:12px;height:3px;background:#3b82f6;border-radius:2px;vertical-align:middle;margin-right:4px;"></span>Historical</span>
                            <span><span style="display:inline-block;width:12px;height:3px;background:#f59e0b;border-radius:2px;vertical-align:middle;margin-right:4px;"></span>Forecast</span>
                            <span><span style="display:inline-block;width:12px;height:12px;background:rgba(245,158,11,0.15);border-radius:2px;vertical-align:middle;margin-right:4px;"></span>Confidence</span>
                        </div>
                    </div>
                    <canvas id="prophetChart" height="100"></canvas>
                </div>

                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #e5e7eb;margin-bottom:24px;">
                    <h3 style="font-size:15px;font-weight:600;color:#374151;margin-bottom:16px;">Forecast Detail</h3>
                    <div style="overflow-x:auto;">
                        <table class="data-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th style="text-align:right;">Forecast</th>
                                    <th style="text-align:right;">Lower Bound</th>
                                    <th style="text-align:right;">Upper Bound</th>
                                    <th style="text-align:right;">Range Width</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['data']['forecast'] ?? [] as $f)
                                    <tr>
                                        <td>{{ $f['ds'] }}</td>
                                        <td style="text-align:right;font-weight:600;">${{ number_format($f['yhat'], 2) }}</td>
                                        <td style="text-align:right;color:#6b7280;">${{ number_format($f['yhat_lower'], 2) }}</td>
                                        <td style="text-align:right;color:#6b7280;">${{ number_format($f['yhat_upper'], 2) }}</td>
                                        <td style="text-align:right;">
                                            <span class="tag tag-blue">${{ number_format($f['yhat_upper'] - $f['yhat_lower'], 2) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" style="text-align:center;color:#9ca3af;">No forecast data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #e5e7eb;margin-bottom:24px;">
                    <h3 style="font-size:15px;font-weight:600;color:#374151;margin-bottom:16px;">Model Components</h3>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        @foreach($s['model_components'] ?? [] as $comp)
                            <span class="tag tag-green">{{ $comp }}</span>
                        @endforeach
                    </div>
                    <p style="font-size:13px;color:#6b7280;margin-top:12px;">
                        Based on {{ $s['total_historical_months'] ?? 0 }} months of historical data.
                        Total historical revenue: ${{ number_format($s['total_historical_revenue'] ?? 0, 2) }}.
                    </p>
                </div>
            @endif
        </div>
    </main>
    @include('layouts.header-actions-js')
    <script>
        @php
            $histJson = $data['data']['historical'] ?? [];
            $fcstJson = $data['data']['forecast'] ?? [];
        @endphp
        const historical = {!! json_encode($histJson) !!};
        const forecast = {!! json_encode($fcstJson) !!};

        if (historical.length || forecast.length) {
            const allLabels = [];
            const histValues = [];
            const forecastValues = [];
            const forecastUpper = [];
            const forecastLower = [];

            historical.forEach(h => {
                allLabels.push(h.ds);
                histValues.push(h.y);
                forecastValues.push(null);
                forecastUpper.push(null);
                forecastLower.push(null);
            });

            forecast.forEach(f => {
                allLabels.push(f.ds);
                histValues.push(null);
                forecastValues.push(f.yhat);
                forecastUpper.push(f.yhat_upper);
                forecastLower.push(f.yhat_lower);
            });

            new Chart(document.getElementById('prophetChart'), {
                type: 'line',
                data: {
                    labels: allLabels,
                    datasets: [
                        {
                            label: 'Historical',
                            data: histValues,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59,130,246,0.08)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 3,
                            pointBackgroundColor: '#3b82f6',
                        },
                        {
                            label: 'Forecast',
                            data: forecastValues,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,0.08)',
                            fill: true,
                            tension: 0.3,
                            borderDash: [5, 5],
                            pointRadius: 4,
                            pointBackgroundColor: '#f59e0b',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        },
                        {
                            label: 'Upper Bound',
                            data: forecastUpper,
                            borderColor: 'rgba(245,158,11,0.2)',
                            backgroundColor: 'rgba(245,158,11,0.1)',
                            fill: '+1',
                            pointRadius: 0,
                            tension: 0.3,
                        },
                        {
                            label: 'Lower Bound',
                            data: forecastLower,
                            borderColor: 'rgba(245,158,11,0.2)',
                            backgroundColor: 'transparent',
                            fill: false,
                            pointRadius: 0,
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': $' + (ctx.parsed.y ? ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits:2}) : 'N/A')
                            }
                        },
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            ticks: { callback: v => '$' + v.toLocaleString() },
                        },
                    },
                    interaction: { mode: 'nearest', axis: 'x', intersect: false },
                },
            });
        }
    </script>
</body>
</html>
