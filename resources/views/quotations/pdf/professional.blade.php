<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e3a5f; line-height: 1.6; }
        .container { padding: 40px; }
        .header { margin-bottom: 40px; }
        .header table { width: 100%; }
        .logo { max-height: 50px; }
        .company-header { font-size: 20px; font-weight: 700; color: #1e3a5f; }
        .quotation-badge { display: inline-block; background: #1e3a5f; color: white; padding: 8px 20px; font-size: 12px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; }
        .divider { height: 3px; background: linear-gradient(90deg, #1e3a5f 0%, #3b82f6 100%); margin: 20px 0; }
        .addresses { margin-bottom: 30px; }
        .addresses table { width: 100%; }
        .addresses td { width: 50%; vertical-align: top; }
        .address-block { padding: 15px; background: #f0f9ff; border-left: 3px solid #1e3a5f; }
        .address-label { font-size: 9px; text-transform: uppercase; letter-spacing: 2px; color: #3b82f6; margin-bottom: 8px; font-weight: 600; }
        .company-name { font-size: 14px; font-weight: 700; color: #1e3a5f; margin-bottom: 5px; }
        .meta-bar { background: #1e3a5f; color: white; padding: 15px 20px; margin-bottom: 30px; }
        .meta-bar table { width: 100%; }
        .meta-bar td { padding: 0 15px; border-right: 1px solid rgba(255,255,255,0.2); }
        .meta-bar td:last-child { border-right: none; }
        .meta-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }
        .meta-value { font-size: 14px; font-weight: 600; margin-top: 3px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background: #f0f9ff; color: #1e3a5f; padding: 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #1e3a5f; }
        .items-table td { padding: 14px 12px; border-bottom: 1px solid #e0f2fe; }
        .items-table tr:hover { background: #f0f9ff; }
        .item-name { font-weight: 600; color: #1e3a5f; }
        .item-description { color: #64748b; font-size: 10px; margin-top: 3px; }
        .totals { margin-left: auto; width: 280px; border: 2px solid #1e3a5f; }
        .totals-row { padding: 10px 15px; border-bottom: 1px solid #e0f2fe; }
        .totals-row table { width: 100%; }
        .totals-row.total { background: #1e3a5f; color: white; font-size: 15px; font-weight: 600; border-bottom: none; }
        .validity { margin-top: 40px; }
        .validity-box { padding: 20px; background: #f0f9ff; border: 1px solid #bae6fd; }
        .validity-title { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: #1e3a5f; margin-bottom: 10px; font-weight: 600; border-bottom: 1px solid #bae6fd; padding-bottom: 5px; }
        .footer { margin-top: 40px; text-align: center; padding: 20px; border-top: 3px solid #1e3a5f; color: #64748b; font-size: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table>
                <tr>
                    <td style="width: 60%;">
                        @if($quotation->company->logo_data_uri)
                        <img src="{{ $quotation->company->logo_data_uri }}" class="logo" alt="">
                        @else
                        <div class="company-header">{{ $quotation->company->name }}</div>
                        @endif
                    </td>
                    <td style="width: 40%; text-align: right;">
                        <span class="quotation-badge">Quotation</span>
                        <div style="margin-top: 10px; font-size: 14px; color: #64748b;">{{ $quotation->quotation_number }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="divider"></div>

        <div class="addresses">
            <table>
                <tr>
                    <td style="padding-right: 15px;">
                        <div class="address-block">
                            <div class="address-label">From</div>
                            <div class="company-name">{{ $quotation->company->name }}</div>
                            @if($quotation->company->address)<div>{{ $quotation->company->address }}</div>@endif
                            @if($quotation->company->city)<div>{{ $quotation->company->city }}, {{ $quotation->company->state }} {{ $quotation->company->postal_code }}</div>@endif
                            @if($quotation->company->email)<div>{{ $quotation->company->email }}</div>@endif
                        </div>
                    </td>
                    <td style="padding-left: 15px;">
                        <div class="address-block">
                            <div class="address-label">Bill To</div>
                            <div class="company-name">{{ $quotation->client->bill_to_name }}</div>
                            @if($quotation->client->bill_to_contact)<div>{{ $quotation->client->bill_to_contact }}</div>@endif
                            @if($quotation->client->address)<div>{{ $quotation->client->address }}</div>@endif
                            @if($quotation->client->city)<div>{{ $quotation->client->city }}, {{ $quotation->client->state }} {{ $quotation->client->postal_code }}</div>@endif
                            @if($quotation->client->email)<div>{{ $quotation->client->email }}</div>@endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="meta-bar">
            <table>
                <tr>
                    <td><div class="meta-label">Quotation Number</div><div class="meta-value">{{ $quotation->quotation_number }}</div></td>
                    <td><div class="meta-label">Issue Date</div><div class="meta-value">{{ $quotation->issue_date->format('M d, Y') }}</div></td>
                    <td><div class="meta-label">Valid Until</div><div class="meta-value">{{ $quotation->valid_until->format('M d, Y') }}</div></td>
                    <td><div class="meta-label">Amount Due</div><div class="meta-value">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</div></td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Description</th>
                    <th style="width: 15%; text-align: center;">Qty</th>
                    <th style="width: 20%; text-align: right;">Unit Price</th>
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
                    <td style="text-align: center;">{{ $item->quantity }} {{ $item->unit }}</td>
                    <td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                    <td style="text-align: right; font-weight: 600;">{{ $quotation->company->currency_symbol }}{{ number_format($item->subtotal, 2) }}</td>
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
            <div class="totals-row total"><table><tr><td>Total Due</td><td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</td></tr></table></div>
        </div>

        @if($quotation->notes || $quotation->terms)
        <div class="validity">
            <div class="validity-box">
                @if($quotation->notes)<div class="validity-title">Notes</div><div style="margin-bottom: 15px;">{{ $quotation->notes }}</div>@endif
                @if($quotation->terms)<div class="validity-title">Terms & Conditions</div><div style="margin-bottom: 15px;">{{ $quotation->terms }}</div>@endif
                <div class="validity-title">Validity</div>
                <div>This quotation is valid until {{ $quotation->valid_until->format('F d, Y') }}</div>
            </div>
        </div>
        @endif

        @include('invoices.pdf.partials.custom-fields', ['doc' => $quotation, 'entity' => 'quotation'])
        @include('quotations.pdf.partials.website-message')

        <div class="footer">{{ $quotation->company->invoice_footer ?? 'Thank you for your business' }}</div>
    </div>
</body>
</html>
