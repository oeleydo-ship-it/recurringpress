<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .container { padding: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .logo { max-height: 60px; }
        .invoice-title { font-size: 32px; font-weight: bold; color: #4f46e5; text-align: right; }
        .invoice-number { color: #666; text-align: right; margin-top: 5px; }
        .addresses { display: table; width: 100%; margin-bottom: 30px; }
        .address-col { display: table-cell; width: 50%; vertical-align: top; }
        .address-label { font-size: 10px; text-transform: uppercase; color: #999; margin-bottom: 5px; font-weight: bold; }
        .company-name { font-size: 16px; font-weight: bold; margin-bottom: 5px; }
        .info-box { background: #f8f9fa; padding: 15px; margin-bottom: 30px; }
        .info-table { width: 100%; }
        .info-table td { padding: 5px 15px 5px 0; }
        .info-label { color: #666; font-size: 10px; text-transform: uppercase; }
        .info-value { font-weight: bold; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background: #4f46e5; color: white; padding: 12px; text-align: left; font-size: 10px; text-transform: uppercase; }
        .items-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .items-table .text-right { text-align: right; }
        .items-table .text-center { text-align: center; }
        .item-name { font-weight: bold; }
        .item-description { color: #666; font-size: 11px; margin-top: 3px; }
        .totals { float: right; width: 250px; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .totals-row.total { border-top: 2px solid #333; font-size: 16px; font-weight: bold; margin-top: 5px; padding-top: 10px; }
        .notes { clear: both; padding-top: 30px; margin-top: 30px; border-top: 1px solid #eee; }
        .notes-title { font-size: 10px; text-transform: uppercase; color: #999; margin-bottom: 5px; font-weight: bold; }
        .notes-content { color: #666; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #999; font-size: 10px; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .status-paid { background: #d1fae5; color: #059669; }
        .status-sent { background: #dbeafe; color: #2563eb; }
        .status-draft { background: #f3f4f6; color: #6b7280; }
        .status-overdue { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        <table style="width: 100%; margin-bottom: 40px;">
            <tr>
                <td style="width: 50%;">
                    @if($invoice->company->logo)
                    <img src="{{ public_path('storage/' . $invoice->company->logo) }}" class="logo" alt="">
                    @endif
                    <div class="company-name" style="margin-top: 10px;">{{ $invoice->company->name }}</div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                    <div style="margin-top: 10px;">
                        <span class="status-badge status-{{ $invoice->status }}">{{ strtoupper($invoice->status) }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <div class="addresses">
            <div class="address-col">
                <div class="address-label">From</div>
                <div class="company-name">{{ $invoice->company->name }}</div>
                @if($invoice->company->address)<div>{{ $invoice->company->address }}</div>@endif
                @if($invoice->company->city)<div>{{ $invoice->company->city }}, {{ $invoice->company->state }} {{ $invoice->company->postal_code }}</div>@endif
                @if($invoice->company->email)<div>{{ $invoice->company->email }}</div>@endif
                @if($invoice->company->phone)<div>{{ $invoice->company->phone }}</div>@endif
                @if($invoice->company->tax_number)<div>Tax ID: {{ $invoice->company->tax_number }}</div>@endif
            </div>
            <div class="address-col">
                <div class="address-label">Bill To</div>
                <div class="company-name">{{ $invoice->client->name }}</div>
                @if($invoice->client->address)<div>{{ $invoice->client->address }}</div>@endif
                @if($invoice->client->city)<div>{{ $invoice->client->city }}, {{ $invoice->client->state }} {{ $invoice->client->postal_code }}</div>@endif
                @if($invoice->client->email)<div>{{ $invoice->client->email }}</div>@endif
                @if($invoice->client->tax_number)<div>Tax ID: {{ $invoice->client->tax_number }}</div>@endif
            </div>
        </div>

        <div class="info-box">
            <table class="info-table">
                <tr>
                    <td>
                        <div class="info-label">Invoice Number</div>
                        <div class="info-value">{{ $invoice->invoice_number }}</div>
                    </td>
                    <td>
                        <div class="info-label">Issue Date</div>
                        <div class="info-value">{{ $invoice->issue_date->format('M d, Y') }}</div>
                    </td>
                    <td>
                        <div class="info-label">Due Date</div>
                        <div class="info-value">{{ $invoice->due_date->format('M d, Y') }}</div>
                    </td>
                    @if($invoice->reference)
                    <td>
                        <div class="info-label">Reference</div>
                        <div class="info-value">{{ $invoice->reference }}</div>
                    </td>
                    @endif
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Description</th>
                    <th class="text-center" style="width: 15%;">Quantity</th>
                    <th class="text-right" style="width: 20%;">Unit Price</th>
                    <th class="text-right" style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <div class="item-name">{{ $item->name }}</div>
                        @if($item->description)
                        <div class="item-description">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }} {{ $item->unit }}</td>
                    <td class="text-right">{{ $invoice->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                    <td class="text-right">{{ $invoice->company->currency_symbol }}{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span>Subtotal</span>
                <span>{{ $invoice->company->currency_symbol }}{{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            <div class="totals-row">
                <span>Tax</span>
                <span>{{ $invoice->company->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</span>
            </div>
            @if($invoice->discount_amount > 0)
            <div class="totals-row">
                <span>Discount</span>
                <span>-{{ $invoice->company->currency_symbol }}{{ number_format($invoice->discount_type === 'percentage' ? ($invoice->subtotal * $invoice->discount_amount / 100) : $invoice->discount_amount, 2) }}</span>
            </div>
            @endif
            <div class="totals-row total">
                <span>Total</span>
                <span>{{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }} {{ $invoice->company->currency }}</span>
            </div>
        </div>

        @if($invoice->notes || $invoice->terms)
        <div class="notes">
            @if($invoice->notes)
            <div style="margin-bottom: 15px;">
                <div class="notes-title">Notes</div>
                <div class="notes-content">{{ $invoice->notes }}</div>
            </div>
            @endif
            @if($invoice->terms)
            <div>
                <div class="notes-title">Terms & Conditions</div>
                <div class="notes-content">{{ $invoice->terms }}</div>
            </div>
            @endif
        </div>
        @endif

        @if($invoice->company->invoice_footer)
        <div class="footer">
            {{ $invoice->company->invoice_footer }}
        </div>
        @endif
    </div>
</body>
</html>
