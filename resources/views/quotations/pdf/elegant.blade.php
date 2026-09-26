<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #44403c; line-height: 1.7; background: #fffbeb; }
        .container { padding: 50px; background: white; max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 2px solid #d97706; }
        .logo { max-height: 50px; margin-bottom: 15px; }
        .quotation-title { font-size: 32px; font-weight: normal; color: #92400e; letter-spacing: 8px; text-transform: uppercase; }
        .quotation-number { color: #a16207; font-size: 14px; margin-top: 10px; font-style: italic; }
        .addresses { margin-bottom: 40px; }
        .addresses table { width: 100%; }
        .addresses td { width: 50%; vertical-align: top; padding: 20px; }
        .addresses td:first-child { border-right: 1px solid #fde68a; }
        .address-label { font-size: 10px; text-transform: uppercase; letter-spacing: 3px; color: #d97706; margin-bottom: 12px; }
        .company-name { font-size: 16px; font-weight: bold; color: #78350f; margin-bottom: 8px; }
        .meta-section { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 20px; margin-bottom: 40px; border-radius: 8px; }
        .meta-section table { width: 100%; }
        .meta-section td { padding: 8px 15px; }
        .meta-label { color: #92400e; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .meta-value { color: #78350f; font-size: 13px; font-weight: bold; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .items-table th { background: #78350f; color: #fef3c7; padding: 14px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .items-table td { padding: 14px; border-bottom: 1px solid #fde68a; }
        .items-table tr:hover { background: #fffbeb; }
        .item-name { font-weight: bold; color: #78350f; }
        .item-description { color: #a16207; font-size: 10px; font-style: italic; margin-top: 4px; }
        .totals { margin-left: auto; width: 280px; }
        .totals-row { padding: 10px 15px; border-bottom: 1px solid #fde68a; }
        .totals-row table { width: 100%; }
        .totals-row.total { background: linear-gradient(135deg, #92400e 0%, #78350f 100%); color: #fef3c7; font-size: 16px; font-weight: bold; border-radius: 4px; border-bottom: none; }
        .validity { margin-top: 50px; padding: 25px; background: #fffbeb; border-left: 4px solid #d97706; border-radius: 0 8px 8px 0; }
        .validity-title { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: #92400e; margin-bottom: 10px; }
        .footer { margin-top: 50px; text-align: center; color: #a16207; font-size: 11px; font-style: italic; padding-top: 20px; border-top: 1px solid #fde68a; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if($quotation->company->logo)
            <img src="{{ public_path('storage/' . $quotation->company->logo) }}" class="logo" alt="">
            @endif
            <div class="quotation-title">Quotation</div>
            <div class="quotation-number">No. {{ $quotation->quotation_number }}</div>
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
                        <div class="address-label">To</div>
                        <div class="company-name">{{ $quotation->client->bill_to_name }}</div>
                        @if($quotation->client->bill_to_contact)<div>{{ $quotation->client->bill_to_contact }}</div>@endif
                        @if($quotation->client->address)<div>{{ $quotation->client->address }}</div>@endif
                        @if($quotation->client->city)<div>{{ $quotation->client->city }}, {{ $quotation->client->state }} {{ $quotation->client->postal_code }}</div>@endif
                        @if($quotation->client->email)<div>{{ $quotation->client->email }}</div>@endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="meta-section">
            <table>
                <tr>
                    <td><div class="meta-label">Quotation Date</div><div class="meta-value">{{ $quotation->issue_date->format('F d, Y') }}</div></td>
                    <td><div class="meta-label">Valid Until</div><div class="meta-value">{{ $quotation->valid_until->format('F d, Y') }}</div></td>
                    <td><div class="meta-label">Amount Due</div><div class="meta-value">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</div></td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Description</th>
                    <th style="width: 15%; text-align: center;">Quantity</th>
                    <th style="width: 20%; text-align: right;">Price</th>
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
                    <td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($item->total, 2) }}</td>
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
            @if($quotation->terms)<div class="validity-title">Terms</div><div>{{ $quotation->terms }}</div>@endif
            <div class="validity-title" style="margin-top: 15px;">Validity</div>
            <div>This quotation is valid until {{ $quotation->valid_until->format('F d, Y') }}</div>
        </div>
        @endif

        @include('quotations.pdf.partials.website-message')

        <div class="footer">{{ $quotation->company->invoice_footer ?? 'Thank you for your business' }}</div>
    </div>
</body>
</html>
