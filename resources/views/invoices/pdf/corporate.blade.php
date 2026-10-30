<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 12mm 12mm; }
        .notes, .footer, .totals { page-break-inside: avoid; }
        .notes { page-break-before: avoid; }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.6; }
        .container { padding: 0; }
        .header { border-bottom: 3px double #1a1a1a; padding-bottom: 20px; margin-bottom: 14px; }
        .header table { width: 100%; }
        .logo { max-height: 60px; }
        .company-header { font-size: 22px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
        .invoice-title { font-size: 14px; text-transform: uppercase; letter-spacing: 3px; border: 2px solid #1a1a1a; padding: 8px 20px; display: inline-block; }
        .addresses { margin-bottom: 14px; }
        .addresses table { width: 100%; }
        .addresses td { width: 50%; vertical-align: top; padding: 10px; }
        .addresses td:first-child { border-right: 1px solid #e5e5e5; }
        .address-label { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: #666; margin-bottom: 10px; font-weight: bold; }
        .company-name { font-size: 14px; font-weight: bold; margin-bottom: 5px; }
        .info-section { background: #f8f8f8; border: 1px solid #e5e5e5; padding: 10px; margin-bottom: 14px; }
        .info-section table { width: 100%; }
        .info-section td { padding: 5px 10px; }
        .info-label { font-weight: bold; text-transform: uppercase; font-size: 9px; letter-spacing: 1px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .items-table th { background: #1a1a1a; color: white; padding: 7px 9px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .items-table td { padding: 7px 9px; border-bottom: 1px solid #e5e5e5; }
        .items-table tr:nth-child(even) { background: #fafafa; }
        .item-name { font-weight: bold; }
        .item-description { color: #666; font-size: 10px; font-style: italic; }
        .totals { margin-left: auto; width: 280px; border: 1px solid #e5e5e5; }
        .totals-row { padding: 7px 12px; border-bottom: 1px solid #e5e5e5; }
        .totals-row table { width: 100%; }
        .totals-row:last-child { border-bottom: none; }
        .totals-row.total { background: #1a1a1a; color: white; font-size: 14px; font-weight: bold; }
        .notes { margin-top: 18px; border: 1px solid #e5e5e5; padding: 8px; }
        .notes-title { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #e5e5e5; padding-bottom: 5px; }
        .footer { margin-top: 18px; text-align: center; font-size: 10px; color: #666; border-top: 3px double #1a1a1a; padding-top: 20px; }
        .stamp { position: absolute; top: 200px; right: 80px; transform: rotate(-15deg); border: 3px solid; padding: 10px 20px; font-size: 18px; font-weight: bold; text-transform: uppercase; opacity: 0.3; }
        .stamp.paid { color: #059669; border-color: #059669; }
        .stamp.overdue { color: #dc2626; border-color: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        @if($invoice->status === 'paid')
        <div class="stamp paid">PAID</div>
        @elseif($invoice->status === 'overdue')
        <div class="stamp overdue">OVERDUE</div>
        @endif

        <div class="header">
            <table>
                <tr>
                    <td style="width: 60%;">
                        @if($invoice->company->logo_data_uri)
                        <img src="{{ $invoice->company->logo_data_uri }}" class="logo" alt="">
                        @else
                        <div class="company-header">{{ $invoice->company->name }}</div>
                        @endif
                        <div style="margin-top: 10px; font-size: 10px; color: #666;">
                            @if($invoice->company->tax_number)Tax ID: {{ $invoice->company->tax_number }}@endif
                        </div>
                    </td>
                    <td style="width: 40%; text-align: right; vertical-align: bottom;">
                        <span class="invoice-title">Invoice</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="addresses">
            <table>
                <tr>
                    <td>
                        <div class="address-label">Seller</div>
                        <div class="company-name">{{ $invoice->company->name }}</div>
                        @if($invoice->company->address)<div>{{ $invoice->company->address }}</div>@endif
                        @if($invoice->company->city)<div>{{ $invoice->company->city }}, {{ $invoice->company->state }} {{ $invoice->company->postal_code }}</div>@endif
                        @if($invoice->company->country)<div>{{ $invoice->company->country }}</div>@endif
                        @if($invoice->company->phone)<div>Tel: {{ $invoice->company->phone }}</div>@endif
                        @if($invoice->company->email)<div>Email: {{ $invoice->company->email }}</div>@endif
                    </td>
                    <td>
                        <div class="address-label">Buyer</div>
                        <div class="company-name">{{ $invoice->client->bill_to_name }}</div>
                        @if($invoice->client->bill_to_contact)<div>{{ $invoice->client->bill_to_contact }}</div>@endif
                        @if($invoice->client->address)<div>{{ $invoice->client->address }}</div>@endif
                        @if($invoice->client->city)<div>{{ $invoice->client->city }}, {{ $invoice->client->state }} {{ $invoice->client->postal_code }}</div>@endif
                        @if($invoice->client->country)<div>{{ $invoice->client->country }}</div>@endif
                        @if($invoice->client->tax_number)<div>Tax ID: {{ $invoice->client->tax_number }}</div>@endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="info-section">
            <table>
                <tr>
                    <td style="width: 25%;"><span class="info-label">Invoice No.</span><br>{{ $invoice->invoice_number }}</td>
                    <td style="width: 25%;"><span class="info-label">Issue Date</span><br>{{ $invoice->issue_date->format('F d, Y') }}</td>
                    <td style="width: 25%;"><span class="info-label">Due Date</span><br>{{ $invoice->due_date->format('F d, Y') }}</td>
                    <td style="width: 25%;"><span class="info-label">Currency</span><br>{{ $invoice->company->currency ?? 'USD' }}</td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 12%; text-align: center;">Quantity</th>
                    <th style="width: 8%; text-align: center;">Unit</th>
                    <th style="width: 15%; text-align: right;">Unit Price</th>
                    <th style="width: 20%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}.</td>
                    <td>
                        <div class="item-name">{{ $item->name }}</div>
                        @if($item->description)<div class="item-description">{{ $item->description }}</div>@endif
                    </td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: center;">{{ $item->unit }}</td>
                    <td style="text-align: right;">{{ $invoice->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                    <td style="text-align: right;">{{ $invoice->company->currency_symbol }}{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <table><tr><td>Subtotal</td><td style="text-align: right;">{{ $invoice->company->currency_symbol }}{{ number_format($invoice->subtotal, 2) }}</td></tr></table>
            </div>
            <div class="totals-row">
                <table><tr><td>Tax</td><td style="text-align: right;">{{ $invoice->company->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</td></tr></table>
            </div>
            @if($invoice->discount_amount > 0)
            <div class="totals-row">
                <table><tr><td>Discount</td><td style="text-align: right;">-{{ $invoice->company->currency_symbol }}{{ number_format($invoice->discount_type === 'percentage' ? ($invoice->subtotal * $invoice->discount_amount / 100) : $invoice->discount_amount, 2) }}</td></tr></table>
            </div>
            @endif
            <div class="totals-row total">
                <table><tr><td>TOTAL DUE</td><td style="text-align: right;">{{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</td></tr></table>
            </div>
        </div>

        @if($invoice->notes || $invoice->terms)
        <div class="notes">
            @if($invoice->notes)
            <div style="margin-bottom: 15px;">
                <div class="notes-title">Notes</div>
                <div>{{ $invoice->notes }}</div>
            </div>
            @endif
            @if($invoice->terms)
            <div>
                <div class="notes-title">Terms & Conditions</div>
                <div>{{ $invoice->terms }}</div>
            </div>
            @endif
        </div>
        @endif

        @include('invoices.pdf.partials.custom-fields', ['doc' => $invoice, 'entity' => 'invoice'])
        @include('invoices.pdf.partials.signature')

        @if($invoice->company->settings->show_invoice_footer ?? true)
        <div class="footer">
            @if($invoice->company->invoice_footer)
            {{ $invoice->company->invoice_footer }}
            @else
            Thank you for your business.
            @endif
        </div>
        @endif
    </div>
</body>
</html>
