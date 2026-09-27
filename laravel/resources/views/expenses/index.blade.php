@extends('roles.layout')

@section('title', 'Expenses')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Expenses</h2>
        <a href="{{ route('expenses.create') }}" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Expense
        </a>
    </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->id }}</td>
                        <td>{{ $expense->description }}</td>
                        <td style="color: var(--error); font-weight: 600;">{{ $currencySymbol }}{{ number_format($expense->amount, 2) }}</td>
                        <td>{{ $expense->transaction->date ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">No expenses found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($expenses->hasPages())
        <div style="padding: 16px;">
            {{ $expenses->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
