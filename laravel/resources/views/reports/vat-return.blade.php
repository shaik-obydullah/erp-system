@extends('roles.layout')

@section('title', 'VAT Return')

@section('content')
<div style="margin-bottom: 24px;">
    <div style="display: flex; gap: 4px; border-bottom: 1px solid var(--border); padding: 0 16px; flex-wrap: wrap;">
        <a href="{{ route('reports.index') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">Overview</a>
        <a href="{{ route('reports.sales') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">Sales</a>
        <a href="{{ route('reports.income') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">Income</a>
        <a href="{{ route('reports.expense') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">Expense</a>
        <a href="{{ route('reports.stock') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">Stock</a>
        <a href="{{ route('reports.customers') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">Customers</a>
        <a href="{{ route('reports.suppliers') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">Suppliers</a>
        <a href="{{ route('reports.vat-return') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px; background: var(--bg-white); color: var(--primary); border-bottom: 2px solid var(--primary);">VAT Return</a>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.vat-return') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 160px;">
                <label for="start_date">Period Start</label>
                <input type="date" name="start_date" id="start_date" class="form-input" value="{{ $startDate }}" style="width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 160px;">
                <label for="end_date">Period End</label>
                <input type="date" name="end_date" id="end_date" class="form-input" value="{{ $endDate }}" style="width: 100%;">
            </div>
            @if($box)
                <input type="hidden" name="box" value="{{ $box }}">
            @endif
            <button type="submit" class="btn btn-primary" style="height: 42px;">Apply Period</button>
            <a href="{{ route('reports.vat-return') }}" class="btn btn-ghost" style="height: 42px;">Clear</a>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h2 class="card-title">VAT Return: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</h2>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            @foreach($boxes as $number => $data)
                <a href="{{ route('reports.vat-return', ['start_date' => $startDate, 'end_date' => $endDate, 'box' => $number]) }}"
                   class="card"
                   style="margin: 0; text-decoration: none; color: inherit; {{ (string) $box === (string) $number ? 'border: 2px solid var(--primary);' : '' }}">
                    <div class="card-body" style="padding: 16px;">
                        <div style="display: flex; justify-content: space-between; gap: 12px; align-items: flex-start;">
                            <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); font-weight: 600;">Box {{ $number }}</div>
                            @if(in_array($number, [2, 8, 9]))
                                <span class="status-badge" style="background: var(--bg-secondary); color: var(--text-secondary);">N/A</span>
                            @endif
                        </div>
                        <div style="font-size: 13px; color: var(--text-secondary); margin: 6px 0 12px; min-height: 34px;">{{ $data['label'] }}</div>
                        <div style="font-size: 24px; font-weight: 700; {{ $number === 5 && $data['value'] < 0 ? 'color: var(--success, #22c55e);' : '' }}">
                            {{ $currencySymbol }}{{ number_format($data['value'], 2) }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        <p style="margin-top: 16px; font-size: 12px; color: var(--text-secondary);">
            Box 5 is the net amount: a positive value is payable to HMRC, a negative value is reclaimable. Boxes 2, 8 and 9 are shown for completeness and are normally zero for a non-EU SME.
        </p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Ledger Reconciliation</h2></div>
        <div class="card-body">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid var(--border);">VAT</th>
                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid var(--border);">Report</th>
                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid var(--border);">Ledger</th>
                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid var(--border);">Difference</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 8px;">Output VAT (Box 1)</td>
                        <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format($reconciliation['output']['report'], 2) }}</td>
                        <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format($reconciliation['output']['ledger'], 2) }}</td>
                        <td style="padding: 8px; text-align: right; color: {{ abs($reconciliation['output']['difference']) < 0.005 ? 'var(--success, #22c55e)' : 'var(--error)' }};">
                            {{ $currencySymbol }}{{ number_format($reconciliation['output']['difference'], 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px;">Input VAT (Box 4)</td>
                        <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format($reconciliation['input']['report'], 2) }}</td>
                        <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format($reconciliation['input']['ledger'], 2) }}</td>
                        <td style="padding: 8px; text-align: right; color: {{ abs($reconciliation['input']['difference']) < 0.005 ? 'var(--success, #22c55e)' : 'var(--error)' }};">
                            {{ $currencySymbol }}{{ number_format($reconciliation['input']['difference'], 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top: 12px; font-size: 12px; color: var(--text-secondary);">
                Report figures come from source documents. Ledger figures come from the <strong>Output VAT payable</strong> and <strong>Input VAT recoverable</strong> accounts. A non-zero difference means a document has not posted to the ledger.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="card-title">Output VAT by Rate</h2></div>
        <div class="card-body">
            @if(empty($rateBreakdown))
                <p style="color: var(--text-secondary); font-size: 14px;">No VAT-rated sales in this period.</p>
            @else
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid var(--border);">Rate</th>
                            <th style="text-align: right; padding: 8px; border-bottom: 1px solid var(--border);">Net</th>
                            <th style="text-align: right; padding: 8px; border-bottom: 1px solid var(--border);">VAT</th>
                            <th style="text-align: right; padding: 8px; border-bottom: 1px solid var(--border);">Gross</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rateBreakdown as $row)
                            <tr>
                                <td style="padding: 8px;">{{ $row['code'] ? ucfirst(str_replace('_', ' ', $row['code'])) : 'Default' }} ({{ rtrim(rtrim(number_format($row['rate'], 2), '0'), '.') }}%)</td>
                                <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format($row['net'], 2) }}</td>
                                <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format($row['vat'], 2) }}</td>
                                <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format($row['gross'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

@if($box)
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Drill-down: Box {{ $box }}</h2>
            <a href="{{ route('reports.vat-return', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-secondary btn-sm">Close</a>
        </div>
        <div class="card-body">
            @if(empty($drillDown))
                <p style="color: var(--text-secondary); font-size: 14px;">
                    @if(in_array($box, ['2', '3', '5', '8', '9']))
                        This box is a calculated total. Drill into Box 1 or Box 4 to see the underlying documents.
                    @else
                        No documents found for this box in the selected period.
                    @endif
                </p>
            @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid var(--border);">Date</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid var(--border);">Reference</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid var(--border);">Description</th>
                                <th style="text-align: right; padding: 8px; border-bottom: 1px solid var(--border);">Net</th>
                                <th style="text-align: right; padding: 8px; border-bottom: 1px solid var(--border);">VAT</th>
                                <th style="text-align: right; padding: 8px; border-bottom: 1px solid var(--border);">Gross</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($drillDown as $row)
                                <tr>
                                    <td style="padding: 8px;">{{ $row['date'] }}</td>
                                    <td style="padding: 8px;">{{ $row['reference'] }}</td>
                                    <td style="padding: 8px;">{{ $row['description'] }}</td>
                                    <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format($row['net'], 2) }}</td>
                                    <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format($row['vat'], 2) }}</td>
                                    <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format($row['gross'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="font-weight: 700; border-top: 2px solid var(--border);">
                                <td colspan="3" style="padding: 8px;">Total</td>
                                <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format(array_sum(array_column($drillDown, 'net')), 2) }}</td>
                                <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format(array_sum(array_column($drillDown, 'vat')), 2) }}</td>
                                <td style="padding: 8px; text-align: right;">{{ $currencySymbol }}{{ number_format(array_sum(array_column($drillDown, 'gross')), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif
@endsection
