<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recommendations — BI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .bi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .bi-card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #e5e7eb; }
        .bi-card .label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
        .bi-card .value { font-size: 28px; font-weight: 700; color: #111827; }
        .data-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #e5e7eb; }
        .data-table th { background: #f9fafb; padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #e5e7eb; }
        .data-table td { padding: 12px 16px; font-size: 14px; color: #374151; border-bottom: 1px solid #f3f4f6; }
        .data-table tr:hover td { background: #f9fafb; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: #3b82f6; text-decoration: none; font-size: 14px; margin-bottom: 16px; }
        .strategy-btn { display: inline-block; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; text-decoration: none; margin-right: 8px; margin-bottom: 8px; border: 1px solid #e5e7eb; color: #374151; background: #fff; transition: all .15s; }
        .strategy-btn:hover { border-color: #3b82f6; color: #3b82f6; }
        .strategy-btn.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
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
            <h1 class="page-title">🎯 Product Recommendations</h1>
            @include('layouts.header-actions')
        </header>
        <div style="padding: 24px;">
            <a href="{{ route('bi.dashboard') }}" class="back-link">← Back to BI Dashboard</a>

            <div style="margin-bottom:20px;">
                <a href="{{ route('bi.recommendations', ['strategy' => 'popular']) }}" class="strategy-btn {{ $strategy === 'popular' ? 'active' : '' }}">Popular</a>
                <a href="{{ route('bi.recommendations', ['strategy' => 'trending']) }}" class="strategy-btn {{ $strategy === 'trending' ? 'active' : '' }}">Trending</a>
                <a href="{{ route('bi.recommendations', ['strategy' => 'content_based']) }}" class="strategy-btn {{ $strategy === 'content_based' ? 'active' : '' }}">Content-Based</a>
                <a href="{{ route('bi.recommendations', ['strategy' => 'personalized']) }}" class="strategy-btn {{ $strategy === 'personalized' ? 'active' : '' }}">Personalized</a>
            </div>

            <div class="bi-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="bi-card">
                    <div class="label">Strategy</div>
                    <div class="value" style="font-size:16px;text-transform:capitalize;">{{ str_replace('_', ' ', $strategy) }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Results</div>
                    <div class="value">{{ $data['count'] ?? 0 }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Model Version</div>
                    <div class="value" style="font-size:16px;">{{ $data['model_version'] ?? '-' }}</div>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Total Sales</th>
                        <th>Avg Rating</th>
                        <th>Popularity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($data['data'] ?? []) as $i => $p)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="font-weight:600;">{{ $p['name'] ?? '-' }}</td>
                        <td>${{ number_format($p['price'] ?? 0, 2) }}</td>
                        <td>{{ $p['total_sales'] ?? $p['recent_sales'] ?? '-' }}</td>
                        <td>{{ $p['avg_rating'] ?? '-' }}</td>
                        <td>{{ number_format($p['popularity'] ?? $p['score'] ?? $p['estimated_rating'] ?? 0, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:24px;">No recommendations available</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
    @include('layouts.header-actions-js')
</body>
</html>
