<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Combos — BI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/css/app.css?v={{ md5_file(public_path('css/app.css')) }}">
    <script src="/js/app.js?v={{ md5_file(public_path('js/app.js')) }}"></script>
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
        .combo-tag { display: inline-block; background: #eff6ff; color: #1d4ed8; padding: 2px 8px; border-radius: 6px; font-size: 12px; margin: 2px; }
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
            <h1 class="page-title">🔗 Product Combo Analysis</h1>
            @include('layouts.header-actions')
        </header>
        <div style="padding: 24px;">
            <a href="{{ route('bi.dashboard') }}" class="back-link">← Back to BI Dashboard</a>

            <div class="bi-grid">
                <div class="bi-card">
                    <div class="label">Transactions Analyzed</div>
                    <div class="value">{{ $data['data']['summary']['total_transactions'] ?? 0 }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Unique Products</div>
                    <div class="value">{{ $data['data']['summary']['total_products'] ?? 0 }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Frequent Combos</div>
                    <div class="value">{{ $data['data']['summary']['frequent_combos'] ?? 0 }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Association Rules</div>
                    <div class="value">{{ $data['data']['summary']['rules_found'] ?? 0 }}</div>
                </div>
            </div>

            {{-- Frequent Itemsets --}}
            <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #e5e7eb;margin-bottom:24px;">
                <h3 style="font-size:15px;font-weight:600;color:#374151;margin-bottom:16px;">Frequent Product Combinations</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Products</th>
                            <th>Support</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($data['data']['frequent_itemsets'] ?? []) as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                @foreach($item['itemsets'] ?? [] as $product)
                                    <span class="combo-tag">{{ $product }}</span>
                                @endforeach
                            </td>
                            <td>{{ ($item['support'] ?? 0) * 100 }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align:center;color:#9ca3af;padding:24px;">No frequent combinations found. Need more transaction data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Association Rules --}}
            @if(count($data['data']['association_rules'] ?? []) > 0)
            <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #e5e7eb;">
                <h3 style="font-size:15px;font-weight:600;color:#374151;margin-bottom:16px;">Association Rules</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>If Customer Buys</th>
                            <th>Then Also Buys</th>
                            <th>Confidence</th>
                            <th>Lift</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['data']['association_rules'] ?? [] as $rule)
                        <tr>
                            <td>
                                @foreach($rule['antecedents'] ?? [] as $a)
                                    <span class="combo-tag">{{ $a }}</span>
                                @endforeach
                            </td>
                            <td>
                                @foreach($rule['consequents'] ?? [] as $c)
                                    <span class="combo-tag" style="background:#d1fae5;color:#065f46;">{{ $c }}</span>
                                @endforeach
                            </td>
                            <td>{{ round(($rule['confidence'] ?? 0) * 100, 1) }}%</td>
                            <td style="font-weight:600; color: {{ ($rule['lift'] ?? 0) > 1 ? '#059669' : '#6b7280' }}">
                                {{ number_format($rule['lift'] ?? 0, 2) }}x
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </main>
    @include('layouts.header-actions-js')
</body>
</html>
