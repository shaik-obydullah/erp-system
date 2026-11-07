<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Credit Note #CN-{{ $saleReturn->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Figtree', sans-serif; font-size: 14px; color: #1f2937; padding: 40px; background: white; }
        .invoice-container { max-width: 900px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid #e5e7eb; }
        .company-name { font-size: 24px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .company-meta { font-size: 13px; color: #4b5563; line-height: 1.6; }
        .invoice-title { font-size: 28px; font-weight: 700; color: #dc2626; text-align: right; }
        .invoice-meta { font-size: 13px; color: #6b7280; margin-top: 8px; text-align: right; line-height: 1.6; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 28px; }
        .info-block h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 8px; }
        .info-block p { font-size: 14px; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { background: #f3f4f6; padding: 10px 8px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600; }
        td { padding: 10px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .totals { display: flex; justify-content: flex-end; }
        .totals-table { width: 340px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; }
        .totals-row.total { border-top: 2px solid #111827; margin-top: 4px; padding-top: 10px; font-size: 18px; font-weight: 700; color: #dc2626; }
        .footer { margin-top: 36px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 12px; color: #9ca3af; }
        @media print { body { padding: 20px; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div>
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-meta">
                    @if($company['address'])<div>{!! nl2br(e($company['address'])) !!}</div>@endif
                    @if($company['country'])<div>{{ $company['country'] }}</div>@endif
                    @if($company['vat_number'])<div><strong>VAT No:</strong> {{ $company['vat_number'] }}</div>@endif
                </div>
            </div>
            <div>
                <div class="invoice-title">CREDIT NOTE</div>
                <div class="invoice-meta">
                    <div><strong>CN-{{ $saleReturn->id }}</strong></div>
                    <div>{{ $saleReturn->created_at ? \Carbon\Carbon::parse($saleReturn->created_at)->format('d M Y') : '—' }}</div>
                    <div>Against invoice <strong>{{ $saleReturn->sale->invoice_id ?? '—' }}</strong></div>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <h3>Credit To</h3>
                @if($saleReturn->sale && $saleReturn->sale->customer)
                    @php $customer = $saleReturn->sale->customer; @endphp
                    <p>
                        <strong>{{ $customer->name }}</strong><br>
                        @if($customer->address){{ $customer->address }}<br>@endif
                        @if($customer->country){{ $customer->country }}<br>@endif
                        @if($customer->email){{ $customer->email }}<br>@endif
                        @if($customer->vat_number)<strong>VAT No:</strong> {{ $customer->vat_number }}@endif
                    </p>
                @else
                    <p>Walk-in Customer</p>
                @endif
            </div>
            <div class="info-block" style="text-align: right;">
                <h3>Reason</h3>
                <p>{{ ucfirst(str_replace('_', ' ', $saleReturn->reason)) }}</p>
                @if($saleReturn->note)<p style="margin-top: 8px; color: #6b7280;">{{ $saleReturn->note }}</p>@endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit Price<br>(excl. VAT)</th>
                    <th class="num">Net</th>
                    <th class="num">VAT Rate</th>
                    <th class="num">VAT</th>
                    <th class="num">Gross</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $net = (float) $saleReturn->net_amount;
                    $vat = (float) $saleReturn->vat_amount;
                    $gross = (float) $saleReturn->refund_amount;
                    $qty = (float) $saleReturn->quantity;
                    $unitNet = $qty > 0 ? $net / $qty : $net;
                @endphp
                <tr>
                    <td>{{ $saleReturn->stock->product->name ?? 'Product' }}</td>
                    <td class="num">{{ $saleReturn->quantity }}</td>
                    <td class="num">{{ $currencySymbol }}{{ number_format($unitNet, 2) }}</td>
                    <td class="num">{{ $currencySymbol }}{{ number_format($net, 2) }}</td>
                    <td class="num">{{ number_format((float) $saleReturn->vat_rate, 0) }}%</td>
                    <td class="num">{{ $currencySymbol }}{{ number_format($vat, 2) }}</td>
                    <td class="num">{{ $currencySymbol }}{{ number_format($gross, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-table">
                <div class="totals-row">
                    <span>Net (excl. VAT):</span>
                    <span>{{ $currencySymbol }}{{ number_format($net, 2) }}</span>
                </div>
                <div class="totals-row">
                    <span>VAT credited:</span>
                    <span>{{ $currencySymbol }}{{ number_format($vat, 2) }}</span>
                </div>
                <div class="totals-row total">
                    <span>Total credited:</span>
                    <span>{{ $currencySymbol }}{{ number_format($gross, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>This credit note reverses output VAT of {{ $currencySymbol }}{{ number_format($vat, 2) }}.</p>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 32px;">
            <button onclick="window.print()" style="padding: 10px 24px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">Print Credit Note</button>
        </div>
    </div>
</body>
</html>
