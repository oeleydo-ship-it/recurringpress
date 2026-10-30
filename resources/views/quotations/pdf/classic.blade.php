<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; line-height: 1.6; }
        .container { padding: 40px; }
        .header { margin-bottom: 40px; }
        .header table { width: 100%; }
        .logo { max-height: 60px; }
        .doc-title { font-size: 28px; font-weight: bold; color: #059669; }
        .doc-number { color: #666; margin-top: 5px; }
        .addresses { margin-bottom: 30px; }
        .addresses table { width: 100%; }
        .addresses td { width: 50%; vertical-align: top; padding-right: 20px; }
        .address-label { font-size: 10px; text-transform: uppercase; color: #999; margin-bottom: 8px; font-weight: bold; letter-spacing: 1px; }
        .company-name { font-size: 14px; font-weight: bold; margin-bottom: 5px; }
        .info-box { background: #f0fdf4; padding: 15px; margin-bottom: 30px; border-radius: 4px; border-left: 4px solid #059669; }
        .info-box table { width: 100%; }
        .info-box td { padding: 5px 15px 5px 0; }
        .info-label { color: #666; font-size: 10px; text-transform: uppercase; }
        .info-value { font-weight: bold; font-size: 12px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background: #059669; color: white; padding: 12px; text-align: left; font-size: 10px; text-transform: uppercase; }
        .items-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        .item-name { font-weight: 600; }
        .item-description { color: #666; font-size: 11px; margin-top: 2px; }
        .totals { margin-left: auto; width: 250px; }
        .totals-row { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .totals-row table { width: 100%; }
        .totals-row.total { border-bottom: none; border-top: 2px solid #059669; font-size: 16px; font-weight: bold; padding-top: 12px; color: #059669; }
        .notes { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        .notes-title { font-size: 10px; text-transform: uppercase; color: #999; margin-bottom: 8px; font-weight: bold; }
        .notes-content { color: #666; }
        .footer { margin-top: 40px; text-align: center; color: #999; font-size: 10px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        .valid-notice { background: #fef3c7; border: 1px solid #f59e0b; padding: 10px; border-radius: 4px; margin-bottom: 20px; color: #92400e; font-size: 11px; }
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
                        <div class="company-name" style="font-size: 20px;">{{ $quotation->company->name }}</div>
                        @endif
                    </td>
                    <td style="width: 40%; text-align: right;">
                        <div class="doc-title">QUOTATION</div>
                        <div class="doc-number">#{{ $quotation->quotation_number }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="valid-notice">
            <strong>Valid Until:</strong> {{ $quotation->valid_until->format('F d, Y') }}
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
                        @if($quotation->company->phone)<div>{{ $quotation->company->phone }}</div>@endif
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

        <div class="info-box">
            <table>
                <tr>
                    <td>
                        <div class="info-label">Quotation Number</div>
                        <div class="info-value">{{ $quotation->quotation_number }}</div>
                    </td>
                    <td>
                        <div class="info-label">Issue Date</div>
                        <div class="info-value">{{ $quotation->issue_date->format('M d, Y') }}</div>
                    </td>
                    <td>
                        <div class="info-label">Valid Until</div>
                        <div class="info-value">{{ $quotation->valid_until->format('M d, Y') }}</div>
                    </td>
                    <td>
                        <div class="info-label">Total</div>
                        <div class="info-value">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Description</th>
                    <th style="width: 15%; text-align: center;">Qty</th>
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
                    <td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <table><tr><td>Subtotal</td><td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->subtotal, 2) }}</td></tr></table>
            </div>
            <div class="totals-row">
                <table><tr><td>Tax</td><td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->tax_amount, 2) }}</td></tr></table>
            </div>
            @if($quotation->discount_amount > 0)
            <div class="totals-row">
                <table><tr><td>Discount</td><td style="text-align: right;">-{{ $quotation->company->currency_symbol }}{{ number_format($quotation->discount_type === 'percentage' ? ($quotation->subtotal * $quotation->discount_amount / 100) : $quotation->discount_amount, 2) }}</td></tr></table>
            </div>
            @endif
            <div class="totals-row total">
                <table><tr><td>Total</td><td style="text-align: right;">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</td></tr></table>
            </div>
        </div>

        @if($quotation->notes || $quotation->terms)
        <div class="notes">
            @if($quotation->notes)
            <div style="margin-bottom: 15px;">
                <div class="notes-title">Notes</div>
                <div class="notes-content">{{ $quotation->notes }}</div>
            </div>
            @endif
            @if($quotation->terms)
            <div>
                <div class="notes-title">Terms & Conditions</div>
                <div class="notes-content">{{ $quotation->terms }}</div>
            </div>
            @endif
        </div>
        @endif

        @include('quotations.pdf.partials.website-message')

        <div class="footer">
            This is a quotation and not an invoice. Prices are subject to change until accepted.
            @if($quotation->company->invoice_footer)<br>{{ $quotation->company->invoice_footer }}@endif
        </div>
    </div>
</body>
</html>
