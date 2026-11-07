@extends('roles.layout')

@section('title', 'Accounting — Chart of Accounts')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <h2 class="card-title">Chart of Accounts</h2>
        <a href="{{ route('accounting.index') }}" class="btn btn-secondary">Back to Accounting</a>
    </div>

    @php
        $labels = [
            'asset' => 'Assets',
            'liability' => 'Liabilities',
            'equity' => 'Equity',
            'revenue' => 'Revenue',
            'expense' => 'Expenses',
        ];
    @endphp

    @foreach($labels as $type => $label)
        <div style="padding:16px 16px 4px;">
            <h3 style="font-weight:700; margin-bottom:8px;">{{ $label }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Normal balance</th>
                        <th>System</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($accounts[$type] ?? collect()) as $account)
                        <tr>
                            <td>{{ $account->code }}</td>
                            <td>{{ $account->name }}</td>
                            <td style="text-transform:capitalize;">{{ $account->normal_balance }}</td>
                            <td>{{ $account->is_system ? 'Default' : 'Custom' }}</td>
                            <td style="color:{{ $account->is_active ? '#15803d' : '#b91c1c' }};">{{ $account->is_active ? 'Active' : 'Inactive' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">No accounts in this group.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
</div>
@endsection
