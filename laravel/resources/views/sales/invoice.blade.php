<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice #{{ $sale->invoice_id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Figtree', sans-serif; font-size: 14px; color: #1f2937; padding: 40px; background: white; }
        .invoice-container { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid #e5e7eb; }
        .company-name { font-size: 24px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .invoice-title { font-size: 28px; font-weight: 700; color: #2563eb; text-align: right; }
        .invoice-meta { font-size: 13px; color: #6b7280; margin-top: 8px; text-align: right; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px; }
        .info-block h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 8px; }
        .info-block p { font-size: 14px; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
        th { background: #f3f4f6; padding: 10px 12px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        .totals { display: flex; justify-content: flex-end; }
        .totals-table { width: 300px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; }
        .totals-row.total { border-top: 2px solid #111827; margin-top: 4px; padding-top: 10px; font-size: 18px; font-weight: 700; }
        .status-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-partial { background: #fef3c7; color: #92400e; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 12px; color: #9ca3af; }
        @media print { body { padding: 20px; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div>
                <div class="company-name">{{ config('app.name', 'ERP System') }}</div>
                <p style="font-size: 13px; color: #6b7280;">{{ config('company.address', '') }}</p>
            </div>
            <div>
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta">
                    <div><strong>#{{ $sale->invoice_id }}</strong></div>
                    <div>{{ $sale->transaction ? \Carbon\Carbon::parse($sale->transaction->date)->format('d M Y') : '—' }}</div>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <h3>Bill To</h3>
                @if($sale->customer)
                    <p><strong>{{ $sale->customer->first_name }} {{ $sale->customer->last_name }}</strong><br>
                    @if($sale->customer->email){{ $sale->customer->email }}<br>@endif
                    @if($sale->customer->phone){{ $sale->customer->phone }}@endif</p>
                @else
                    <p>Walk-in Customer</p>
                @endif
            </div>
            <div class="info-block" style="text-align: right;">
                <h3>Status</h3>
                <p>
                    <span class="status-badge {{ $sale->status === 'paid' ? 'badge-paid' : ($sale->status === 'partial' ? 'badge-partial' : 'badge-unpaid') }}">
                        {{ ucfirst($sale->status) }}
                    </span>
                </p>
                <div style="margin-top: 12px;">
                    <h3>Type</h3>
                    <p>{{ ucfirst($sale->type) }}</p>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th style="text-align: right;">Qty</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->saleItems as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->stock_name ?? '—' }}</td>
                        <td style="text-align: right;">{{ $item->sale_stock }}</td>
                        <td style="text-align: right;">{{ $currencySymbol }}{{ number_format($item->sale_stock > 0 ? $item->subtotal / $item->sale_stock : 0, 2) }}</td>
                        <td style="text-align: right;">{{ $currencySymbol }}{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-table">
                <div class="totals-row">
                    <span>Subtotal:</span>
                    <span>{{ $currencySymbol }}{{ number_format($sale->grand_total - $sale->shipping_cost + $sale->discount_amount, 2) }}</span>
                </div>
                @if($sale->discount_amount > 0)
                <div class="totals-row">
                    <span>Discount:</span>
                    <span>-{{ $currencySymbol }}{{ number_format($sale->discount_amount, 2) }}</span>
                </div>
                @endif
                @if($sale->shipping_cost > 0)
                <div class="totals-row">
                    <span>Shipping:</span>
                    <span>{{ $currencySymbol }}{{ number_format($sale->shipping_cost, 2) }}</span>
                </div>
                @endif
                <div class="totals-row">
                    <span>Paid:</span>
                    <span>{{ $currencySymbol }}{{ number_format($sale->paid_amount, 2) }}</span>
                </div>
                <div class="totals-row total">
                    <span>Grand Total:</span>
                    <span>{{ $currencySymbol }}{{ number_format($sale->grand_total, 2) }}</span>
                </div>
                @if($sale->sale_due > 0)
                <div class="totals-row" style="color: #dc2626; font-weight: 600;">
                    <span>Due:</span>
                    <span>{{ $currencySymbol }}{{ number_format($sale->sale_due, 2) }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 32px;">
            <button onclick="window.print()" style="padding: 10px 24px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">Print Invoice</button>
        </div>
    </div>
</body>
</html>
