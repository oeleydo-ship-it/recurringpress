<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .container { padding: 40px; }
        .header { margin-bottom: 30px; }
        .header table { width: 100%; }
        .logo { max-height: 40px; }
        .quotation-title { font-size: 24px; font-weight: bold; }
        .quotation-number { color: #666; }
        .addresses { margin-bottom: 30px; }
        .addresses table { width: 100%; }
        .addresses td { width: 50%; vertical-align: top; }
        .address-label { font-weight: bold; margin-bottom: 5px; color: #666; font-size: 11px; text-transform: uppercase; }
        .company-name { font-weight: bold; margin-bottom: 3px; }
        .meta { margin-bottom: 30px; padding: 15px; background: #f5f5f5; }
        .meta table { width: 100%; }
        .meta td { padding: 5px 15px 5px 0; }
        .meta-label { color: #666; font-size: 11px; }
        .meta-value { font-weight: bold; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background: #333; color: white; padding: 10px; text-align: left; font-size: 11px; }
        .items-table td { padding: 10px; border-bottom: 1px solid #ddd; }
        .item-description { color: #666; font-size: 11px; }
        .totals { margin-left: auto; width: 200px; }
        .totals-row { padding: 5px 0; }
        .totals-row table { width: 100%; }
        .totals-row.total { border-top: 2px solid #333; font-weight: bold; font-size: 14px; padding-top: 10px; }
        .validity { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
        .validity-title { font-weight: bold; margin-bottom: 5px; color: #666; font-size: 11px; text-transform: uppercase; }
        .footer { margin-top: 30px; text-align: center; color: #999; font-size: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table>
                <tr>
                    <td>
                        @if($quotation->company->logo)
                        <img src="{{ public_path('storage/' . $quotation->company->logo) }}" class="logo" alt="">
                        @else
                        <div style="font-weight: bold; font-size: 16px;">{{ $quotation->company->name }}</div>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div class="quotation-title">QUOTATION</div>
                        <div class="quotation-number">#{{ $quotation->quotation_number }}</div>
                    </td>
                </tr>
            </table>
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

        <div class="meta">
            <table>
                <tr>
                    <td><span class="meta-label">Quotation Number:</span> <span class="meta-value">{{ $quotation->quotation_number }}</span></td>
                    <td><span class="meta-label">Date:</span> <span class="meta-value">{{ $quotation->issue_date->format('M d, Y') }}</span></td>
                    <td><span class="meta-label">Valid Until:</span> <span class="meta-value">{{ $quotation->valid_until->format('M d, Y') }}</span></td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 10%; text-align: center;">Qty</th>
                    <th style="width: 20%; text-align: right;">Price</th>
                    <th style="width: 20%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $item)
                <tr>
                    <td>
                        {{ $item->name }}
                        @if($item->description)<div class="item-description">{{ $item->description }}</div>@endif
                    </td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                    <td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row"><table><tr><td>Subtotal</td><td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->subtotal, 2) }}</td></tr></table></div>
            @if($quotation->tax_amount > 0)
            <div class="totals-row"><table><tr><td>Tax</td><td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->tax_amount, 2) }}</td></tr></table></div>
            @endif
            @if($quotation->discount_amount > 0)
            <div class="totals-row"><table><tr><td>Discount</td><td style="text-align: right;">-{{ $quotation->company->currency_symbol }}{{ number_format($quotation->discount_type === 'percentage' ? ($quotation->subtotal * $quotation->discount_amount / 100) : $quotation->discount_amount, 2) }}</td></tr></table></div>
            @endif
            <div class="totals-row total"><table><tr><td>Total</td><td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</td></tr></table></div>
        </div>

        @if($quotation->notes || $quotation->terms)
        <div class="validity">
            @if($quotation->notes)<div class="validity-title">Notes</div><div style="margin-bottom: 10px;">{{ $quotation->notes }}</div>@endif
            @if($quotation->terms)<div class="validity-title">Terms</div><div>{{ $quotation->terms }}</div>@endif
            <div class="validity-title">Validity</div>
            <div>This quotation is valid until {{ $quotation->valid_until->format('F d, Y') }}</div>
        </div>
        @endif

        @include('quotations.pdf.partials.website-message')

        @if($quotation->company->invoice_footer)
        <div class="footer">{{ $quotation->company->invoice_footer }}</div>
        @endif
    </div>
</body>
</html>
