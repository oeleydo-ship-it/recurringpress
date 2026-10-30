<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #374151; line-height: 1.6; }
        .container { padding: 60px; }
        .header { margin-bottom: 50px; }
        .quotation-title { font-size: 24px; font-weight: 300; letter-spacing: 3px; text-transform: uppercase; color: #111827; }
        .quotation-number { color: #6b7280; font-size: 14px; margin-top: 10px; }
        .addresses { margin-bottom: 50px; }
        .addresses table { width: 100%; }
        .addresses td { width: 50%; vertical-align: top; padding-right: 40px; }
        .address-label { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: #9ca3af; margin-bottom: 15px; font-weight: 500; }
        .company-name { font-size: 16px; font-weight: 500; color: #111827; margin-bottom: 8px; }
        .meta { margin-bottom: 40px; padding: 20px 0; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; }
        .meta table { width: 100%; }
        .meta td { padding: 8px 0; }
        .meta-label { color: #6b7280; font-size: 11px; }
        .meta-value { color: #111827; font-weight: 500; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .items-table th { padding: 12px 0; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        .items-table td { padding: 16px 0; border-bottom: 1px solid #f3f4f6; }
        .item-name { font-weight: 500; color: #111827; }
        .item-description { color: #6b7280; font-size: 10px; margin-top: 4px; }
        .totals { margin-left: auto; width: 200px; }
        .totals-row { padding: 8px 0; }
        .totals-row table { width: 100%; }
        .totals-row.total { border-top: 1px solid #111827; padding-top: 12px; font-weight: 600; }
        .validity { margin-top: 60px; padding-top: 30px; border-top: 1px solid #e5e7eb; }
        .validity-title { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: #9ca3af; margin-bottom: 8px; font-weight: 500; }
        .footer { margin-top: 60px; text-align: center; color: #9ca3af; font-size: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="quotation-title">Quotation</div>
            <div class="quotation-number">{{ $quotation->quotation_number }}</div>
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
                    <td><span class="meta-label">Total:</span> <span class="meta-value">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</span></td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 10%; text-align: center;">Qty</th>
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
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                    <td style="text-align: right; font-weight: 500;">{{ $quotation->company->currency_symbol }}{{ number_format($item->subtotal, 2) }}</td>
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
            @if($quotation->notes)<div class="validity-title">Notes</div><div style="margin-bottom: 20px;">{{ $quotation->notes }}</div>@endif
            @if($quotation->terms)<div class="validity-title">Terms & Conditions</div><div style="margin-bottom: 20px;">{{ $quotation->terms }}</div>@endif
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
