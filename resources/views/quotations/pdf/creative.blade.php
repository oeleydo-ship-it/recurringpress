<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #374151; line-height: 1.6; }
        .container { padding: 0; }
        .header { background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 50%, #6366f1 100%); color: white; padding: 40px; position: relative; overflow: hidden; }
        .header::before { content: ''; position: absolute; top: -50%; right: -20%; width: 60%; height: 200%; background: rgba(255,255,255,0.1); transform: rotate(15deg); }
        .header-content { position: relative; z-index: 1; }
        .logo { max-height: 40px; margin-bottom: 20px; }
        .quotation-title { font-size: 42px; font-weight: 800; letter-spacing: -2px; }
        .quotation-number { opacity: 0.9; font-size: 16px; margin-top: 5px; }
        .content { padding: 40px; }
        .info-cards { display: table; width: 100%; margin-bottom: 40px; }
        .info-card { display: table-cell; width: 25%; padding: 15px; background: linear-gradient(135deg, #fdf4ff 0%, #faf5ff 100%); border-radius: 12px; margin-right: 10px; }
        .info-card:last-child { margin-right: 0; }
        .info-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #a855f7; font-weight: 600; }
        .info-value { font-size: 14px; font-weight: 700; color: #581c87; margin-top: 5px; }
        .addresses { margin-bottom: 40px; }
        .addresses table { width: 100%; }
        .addresses td { width: 50%; vertical-align: top; padding-right: 30px; }
        .address-label { font-size: 9px; text-transform: uppercase; letter-spacing: 2px; color: #a855f7; margin-bottom: 10px; font-weight: 600; }
        .company-name { font-size: 16px; font-weight: 700; color: #581c87; margin-bottom: 5px; }
        .items-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; margin-bottom: 30px; }
        .items-table th { background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%); color: white; padding: 14px 16px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .items-table th:first-child { border-radius: 8px 0 0 8px; }
        .items-table th:last-child { border-radius: 0 8px 8px 0; }
        .items-table td { padding: 16px; background: #faf5ff; }
        .items-table tr td:first-child { border-radius: 8px 0 0 8px; }
        .items-table tr td:last-child { border-radius: 0 8px 8px 0; }
        .item-name { font-weight: 600; color: #581c87; }
        .item-description { color: #9333ea; font-size: 10px; margin-top: 3px; }
        .totals { margin-left: auto; width: 280px; }
        .totals-row { padding: 10px 16px; margin-bottom: 4px; background: #faf5ff; border-radius: 6px; }
        .totals-row table { width: 100%; }
        .totals-row.total { background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%); color: white; font-size: 16px; font-weight: 700; border-radius: 10px; }
        .validity { margin-top: 40px; padding: 20px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; border-left: 4px solid #d97706; }
        .validity-title { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: #92400e; margin-bottom: 10px; font-weight: 600; }
        .footer { margin-top: 40px; text-align: center; padding: 20px; color: #9333ea; font-size: 11px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                @if($quotation->company->logo_data_uri)
                <img src="{{ $quotation->company->logo_data_uri }}" class="logo" alt="">
                @else
                <div style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">{{ $quotation->company->name }}</div>
                @endif
                <div class="quotation-title">QUOTATION</div>
                <div class="quotation-number">#{{ $quotation->quotation_number }}</div>
            </div>
        </div>

        <div class="content">
            <div class="info-cards">
                <div class="info-card"><div class="info-label">Quotation No.</div><div class="info-value">{{ $quotation->quotation_number }}</div></div>
                <div class="info-card"><div class="info-label">Issue Date</div><div class="info-value">{{ $quotation->issue_date->format('M d, Y') }}</div></div>
                <div class="info-card"><div class="info-label">Valid Until</div><div class="info-value">{{ $quotation->valid_until->format('M d, Y') }}</div></div>
                <div class="info-card"><div class="info-value">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</div></div>
            </div>

            <div class="addresses">
                <table>
                    <tr>
                        <td>
                            <div class="address-label">From</div>
                            <div class="company-name">{{ $quotation->company->name }}</div>
                            @if($quotation->company->address)<div>{{ $quotation->company->address }}</div>@endif
                            @if($quotation->company->city)<div>{{ $quotation->company->city }}, {{ $quotation->company->state }} {{ $quotation->company->postal_code }}</div>@endif
                            @if($quotation->company->email)<div>{{ $quotation->company->email }}</div>@endif
                        </td>
                        <td>
                            <div class="address-label">Bill To</div>
                            <div class="company-name">{{ $quotation->client->bill_to_name }}</div>
                            @if($quotation->client->bill_to_contact)<div>{{ $quotation->client->bill_to_contact }}</div>@endif
                            @if($quotation->client->address)<div>{{ $quotation->client->address }}</div>@endif
                            @if($quotation->client->city)<div>{{ $quotation->client->city }}, {{ $quotation->client->state }} {{ $quotation->client->postal_code }}</div>@endif
                            @if($quotation->client->email)<div>{{ $quotation->client->email }}</div>@endif
                        </td>
                    </tr>
                </table>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 45%;">Item</th>
                        <th style="width: 15%; text-align: center;">Qty</th>
                        <th style="width: 20%; text-align: right;">Rate</th>
                        <th style="width: 20%; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quotation->items as $item)
                    <tr>
                        <td>
                            <div class="item-name">{{ $item->name }}</div>
                            @if($item->description)<div class="item-description">{{ $item->description }}</div>@endif
                        </td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                        <td style="text-align: right; font-weight: 600;">{{ $quotation->company->currency_symbol }}{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                <div class="totals-row"><table><tr><td>Subtotal</td><td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->subtotal, 2) }}</td></tr></table></div>
                <div class="totals-row"><table><tr><td>Tax</td><td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->tax_amount, 2) }}</td></tr></table></div>
                @if($quotation->discount_amount > 0)
                <div class="totals-row"><table><tr><td>Discount</td><td style="text-align: right;">-{{ $quotation->company->currency_symbol }}{{ number_format($quotation->discount_type === 'percentage' ? ($quotation->subtotal * $quotation->discount_amount / 100) : $quotation->discount_amount, 2) }}</td></tr></table></div>
                @endif
                <div class="totals-row total"><table><tr><td>Total</td><td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</td></tr></table></div>
            </div>

            @if($quotation->notes || $quotation->terms)
            <div class="validity">
                @if($quotation->notes)<div class="validity-title">Notes</div><div style="margin-bottom: 15px;">{{ $quotation->notes }}</div>@endif
                @if($quotation->terms)<div class="validity-title">Terms & Conditions</div><div>{{ $quotation->terms }}</div>@endif
                <div class="validity-title" style="margin-top: 15px;">Validity</div>
                <div>This quotation is valid until {{ $quotation->valid_until->format('F d, Y') }}</div>
            </div>
            @endif

            @include('quotations.pdf.partials.website-message')

            <div class="footer">{{ $quotation->company->invoice_footer ?? 'Thank you for your business!' }}</div>
        </div>
    </div>
</body>
</html>
