<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Performance — BI</title>
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
        .data-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #e5e7eb; }
        .data-table th { background: #f9fafb; padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #e5e7eb; }
        .data-table td { padding: 12px 16px; font-size: 14px; color: #374151; border-bottom: 1px solid #f3f4f6; }
        .data-table tr:hover td { background: #f9fafb; }
        .tier-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .tier-high { background: #d1fae5; color: #065f46; }
        .tier-medium { background: #fef3c7; color: #92400e; }
        .tier-low { background: #fee2e2; color: #991b1b; }
        .anomaly-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background: #fee2e2; color: #991b1b; }
        .charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        @media (max-width: 768px) { .charts-row { grid-template-columns: 1fr; } }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: #3b82f6; text-decoration: none; font-size: 14px; margin-bottom: 16px; }
        .back-link:hover { text-decoration: underline; }
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
            <h1 class="page-title">👥 Employee Performance</h1>
            @include('layouts.header-actions')
        </header>
        <div style="padding: 24px;">
            <a href="{{ route('bi.dashboard') }}" class="back-link">← Back to BI Dashboard</a>

            <div class="bi-grid">
                <div class="bi-card">
                    <div class="label">Total Employees</div>
                    <div class="value">{{ $data['summary']['total_employees'] ?? 0 }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Avg Score</div>
                    <div class="value">{{ $data['summary']['avg_score'] ?? 0 }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">High Performers</div>
                    <div class="value" style="color:#059669">{{ $data['summary']['high_performers'] ?? 0 }}</div>
                </div>
                <div class="bi-card">
                    <div class="label">Anomalies Detected</div>
                    <div class="value" style="color:#dc2626">{{ $data['summary']['anomalies_detected'] ?? 0 }}</div>
                </div>
            </div>

            <div class="charts-row">
                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #e5e7eb;">
                    <h3 style="font-size:15px;font-weight:600;color:#374151;margin-bottom:16px;">Performance Scores</h3>
                    <canvas id="perfChart" height="200"></canvas>
                </div>
                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #e5e7eb;">
                    <h3 style="font-size:15px;font-weight:600;color:#374151;margin-bottom:16px;">Task Distribution</h3>
                    <canvas id="taskChart" height="200"></canvas>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Job Title</th>
                        <th>Total Tasks</th>
                        <th>Completed</th>
                        <th>Completion Rate</th>
                        <th>Score</th>
                        <th>Tier</th>
                        <th>Anomaly</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($data['data'] ?? []) as $emp)
                    <tr>
                        <td style="font-weight:600;">{{ $emp['name'] ?? '-' }}</td>
                        <td>{{ $emp['job_title'] ?? '-' }}</td>
                        <td>{{ $emp['total_tasks'] ?? 0 }}</td>
                        <td>{{ $emp['completed_tasks'] ?? 0 }}</td>
                        <td>{{ round(($emp['completion_rate'] ?? 0) * 100, 1) }}%</td>
                        <td style="font-weight:600;">{{ $emp['performance_score'] ?? 0 }}</td>
                        <td>
                            <span class="tier-badge tier-{{ strtolower($emp['performance_tier'] ?? 'low') }}">{{ $emp['performance_tier'] ?? '-' }}</span>
                        </td>
                        <td>
                            @if($emp['anomaly'] ?? false)
                                <span class="anomaly-badge">⚠ Yes</span>
                            @else
                                <span style="color:#9ca3af;">No</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:24px;">No employee data found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>

    @include('layouts.header-actions-js')
    <script>
        @php $employeesJson = $data['data'] ?? []; @endphp
        const employees = {!! json_encode($employeesJson) !!};
        if (employees.length) {
            new Chart(document.getElementById('perfChart'), {
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

            new Chart(document.getElementById('taskChart'), {
                type: 'doughnut',
                data: {
                    labels: employees.map(e => e.name),
                    datasets: [{
                        data: employees.map(e => e.completed_tasks),
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899']
                    }]
                },
                options: { responsive: true }
            });
        }
    </script>
</body>
</html>
