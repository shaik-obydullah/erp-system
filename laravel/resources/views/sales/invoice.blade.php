<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VAT Invoice #{{ $sale->invoice_id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Figtree', sans-serif; font-size: 14px; color: #1f2937; padding: 40px; background: white; }
        .invoice-container { max-width: 900px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid #e5e7eb; }
        .company-name { font-size: 24px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .company-meta { font-size: 13px; color: #4b5563; line-height: 1.6; }
        .invoice-title { font-size: 28px; font-weight: 700; color: #2563eb; text-align: right; }
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
        .totals-row.total { border-top: 2px solid #111827; margin-top: 4px; padding-top: 10px; font-size: 18px; font-weight: 700; }
        .status-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-partial { background: #fef3c7; color: #92400e; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        .vat-summary { margin-top: 28px; page-break-inside: avoid; }
        .vat-summary h3 { font-size: 13px; font-weight: 700; margin-bottom: 8px; }
        .footer { margin-top: 36px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 12px; color: #9ca3af; }
        @media print { body { padding: 20px; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    @php
        $lines = $sale->saleItems;
        $detailsNet = 0.0;
        $detailsVat = 0.0;
        $detailsGross = 0.0;

        foreach ($lines as $line) {
            $lineGross = (float) ($line->gross_amount ?? $line->subtotal ?? 0);
            $lineVat = (float) ($line->vat_amount ?? 0);
            $lineNet = (float) ($line->net_amount ?? ($lineGross - $lineVat));

            $detailsNet += $lineNet;
            $detailsVat += $lineVat;
            $detailsGross += $lineGross;
        }

        $saleNet = (float) ($sale->net_price ?? $detailsNet);
        $saleVat = (float) ($sale->vat_amount ?? $detailsVat);
        $shippingNet = max(0, round($saleNet - $detailsNet, 2));
        $shippingVat = max(0, round($saleVat - $detailsVat, 2));
        $shippingGross = round($shippingNet + $shippingVat, 2);

        $breakdown = $sale->vat_breakdown ?? [];
    @endphp

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
                <div class="invoice-title">VAT INVOICE</div>
                <div class="invoice-meta">
                    <div><strong>#{{ $sale->invoice_id }}</strong></div>
                    <div>{{ $sale->transaction ? \Carbon\Carbon::parse($sale->transaction->date)->format('d M Y') : '—' }}</div>
                    <div>Status:
                        <span class="status-badge {{ $sale->status === 'paid' ? 'badge-paid' : ($sale->status === 'partial' ? 'badge-partial' : 'badge-unpaid') }}">
                            {{ ucfirst($sale->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <h3>Bill To</h3>
                @if($sale->customer)
                    <p>
                        <strong>{{ $sale->customer->name }}</strong><br>
                        @if($sale->customer->address){{ $sale->customer->address }}<br>@endif
                        @if($sale->customer->country){{ $sale->customer->country }}<br>@endif
                        @if($sale->customer->email){{ $sale->customer->email }}<br>@endif
                        @if($sale->customer->phone){{ $sale->customer->phone }}<br>@endif
                        @if($sale->customer->vat_number)<strong>VAT No:</strong> {{ $sale->customer->vat_number }}@endif
                    </p>
                @else
                    <p>Walk-in Customer</p>
                @endif
            </div>
            <div class="info-block" style="text-align: right;">
                <h3>Sale Details</h3>
                <p>
                    Type: {{ ucfirst($sale->type) }}<br>
                    @if($sale->note)Note: {{ $sale->note }}<br>@endif
                    Payment: {{ ucfirst($sale->status) }}
                </p>
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
                @foreach($lines as $item)
                    @php
                        $gross = (float) ($item->gross_amount ?? $item->subtotal ?? 0);
                        $vat = (float) ($item->vat_amount ?? 0);
                        $net = (float) ($item->net_amount ?? ($gross - $vat));
                        $qty = (float) ($item->sale_stock ?? 0);
                        $unitNet = $qty > 0 ? $net / $qty : $net;
                        $rate = (float) ($item->vat_rate ?? 0);
                    @endphp
                    <tr>
                        <td>
                            {{ $item->stock_name ?? '—' }}
                            @if($item->size || $item->color)
                                <div style="font-size: 12px; color: #6b7280;">
                                    {{ collect([$item->size, $item->color])->filter()->implode(' / ') }}
                                </div>
                            @endif
                        </td>
                        <td class="num">{{ $qty }}</td>
                        <td class="num">{{ $currencySymbol }}{{ number_format($unitNet, 2) }}</td>
                        <td class="num">{{ $currencySymbol }}{{ number_format($net, 2) }}</td>
                        <td class="num">{{ number_format($rate, 0) }}%</td>
                        <td class="num">{{ $currencySymbol }}{{ number_format($vat, 2) }}</td>
                        <td class="num">{{ $currencySymbol }}{{ number_format($gross, 2) }}</td>
                    </tr>
                @endforeach

                @if($shippingGross > 0)
                    <tr>
                        <td>Shipping</td>
                        <td class="num">1</td>
                        <td class="num">{{ $currencySymbol }}{{ number_format($shippingNet, 2) }}</td>
                        <td class="num">{{ $currencySymbol }}{{ number_format($shippingNet, 2) }}</td>
                        <td class="num">—</td>
                        <td class="num">{{ $currencySymbol }}{{ number_format($shippingVat, 2) }}</td>
                        <td class="num">{{ $currencySymbol }}{{ number_format($shippingGross, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if(count($breakdown))
            <div class="vat-summary">
                <h3>VAT Summary</h3>
                <table>
                    <thead>
                        <tr>
                            <th>VAT Rate</th>
                            <th class="num">Net</th>
                            <th class="num">VAT</th>
                            <th class="num">Gross</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($breakdown as $band)
                            <tr>
                                <td>
                                    {{ number_format((float) ($band['rate'] ?? 0), 0) }}%
                                    @if(!empty($band['label']))
                                        <span style="color: #6b7280; font-size: 12px;">({{ $band['label'] }})</span>
                                    @endif
                                </td>
                                <td class="num">{{ $currencySymbol }}{{ number_format((float) ($band['net'] ?? 0), 2) }}</td>
                                <td class="num">{{ $currencySymbol }}{{ number_format((float) ($band['vat'] ?? 0), 2) }}</td>
                                <td class="num">{{ $currencySymbol }}{{ number_format((float) ($band['gross'] ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="totals">
            <div class="totals-table">
                <div class="totals-row">
                    <span>Net (excl. VAT):</span>
                    <span>{{ $currencySymbol }}{{ number_format($saleNet, 2) }}</span>
                </div>
                <div class="totals-row">
                    <span>VAT:</span>
                    <span>{{ $currencySymbol }}{{ number_format($saleVat, 2) }}</span>
                </div>
                @if($sale->discount_amount > 0)
                    <div class="totals-row" style="color: #6b7280;">
                        <span>Discount applied:</span>
                        <span>-{{ $currencySymbol }}{{ number_format($sale->discount_amount, 2) }}</span>
                    </div>
                @endif
                @if($shippingGross > 0)
                    <div class="totals-row">
                        <span>Shipping (incl. VAT):</span>
                        <span>{{ $currencySymbol }}{{ number_format($shippingGross, 2) }}</span>
                    </div>
                @endif
                <div class="totals-row">
                    <span>Paid:</span>
                    <span>{{ $currencySymbol }}{{ number_format($sale->paid_amount, 2) }}</span>
                </div>
                <div class="totals-row total">
                    <span>Total (incl. VAT):</span>
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
            @if($company['vat_number'])
                <p>VAT invoice issued by {{ $company['name'] }}, VAT registration {{ $company['vat_number'] }}.</p>
            @endif
        </div>

        <div class="no-print" style="text-align: center; margin-top: 32px;">
            <button onclick="window.print()" style="padding: 10px 24px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">Print Invoice</button>
        </div>
    </div>
</body>
</html>
