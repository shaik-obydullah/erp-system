@extends('roles.layout')

@section('title', 'Payrolls')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Payrolls</h2>
            <a href="{{ route('payrolls.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Payroll
            </a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Basic Salary</th>
                    <th>Allowances</th>
                    <th>Deductions</th>
                    <th>Net Salary</th>
                    <th>Pay Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrolls as $payroll)
                    <tr id="row-{{ $payroll->id }}">
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; flex-shrink: 0;">
                                    {{ substr($payroll->employee->first_name ?? 'E', 0, 1) }}
                                </div>
                                <strong>{{ $payroll->employee->first_name ?? '' }} {{ $payroll->employee->last_name ?? '' }}</strong>
                            </div>
                        </td>
                        <td>{{ $currencySymbol }}{{ number_format($payroll->basic_salary, 2) }}</td>
                        <td style="color: var(--success, #22c55e);">{{ $currencySymbol }}{{ number_format($payroll->allowances, 2) }}</td>
                        <td style="color: var(--error);">{{ $currencySymbol }}{{ number_format($payroll->deductions, 2) }}</td>
                        <td><strong>{{ $currencySymbol }}{{ number_format($payroll->net_salary, 2) }}</strong></td>
                        <td>{{ $payroll->pay_date ? \Carbon\Carbon::parse($payroll->pay_date)->format('d M Y') : '—' }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('payrolls.edit', $payroll) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">No payrolls found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($payrolls->hasPages())
        <div style="padding: 16px;">
            {{ $payrolls->links() }}
        </div>
        @endif
    </div>
@endsection
