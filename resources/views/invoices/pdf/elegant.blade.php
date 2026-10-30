<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 12mm 12mm; }
        .notes, .footer, .totals { page-break-inside: avoid; }
        .notes { page-break-before: avoid; }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #44403c;
            line-height: 1.7;
            background: #fffbeb;
        }

        .container {
            padding: 50px;
            background: white;
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 18px;
            padding-bottom: 30px;
            border-bottom: 2px solid #d97706;
        }

        .logo {
            max-height: 50px;
            margin-bottom: 15px;
        }

        .invoice-title {
            font-size: 32px;
            font-weight: normal;
            color: #92400e;
            letter-spacing: 8px;
            text-transform: uppercase;
        }

        .invoice-number {
            color: #a16207;
            font-size: 14px;
            margin-top: 10px;
            font-style: italic;
        }

        .addresses {
            margin-bottom: 18px;
        }

        .addresses table {
            width: 100%;
        }

        .addresses td {
            width: 50%;
            vertical-align: top;
            padding: 8px;
        }

        .addresses td:first-child {
            border-right: 1px solid #fde68a;
        }

        .address-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #d97706;
            margin-bottom: 12px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #78350f;
            margin-bottom: 8px;
        }

        .meta-section {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 8px;
            margin-bottom: 18px;
            border-radius: 8px;
        }

        .meta-section table {
            width: 100%;
        }

        .meta-section td {
            padding: 8px 15px;
        }

        .meta-label {
            color: #92400e;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .meta-value {
            color: #78350f;
            font-size: 13px;
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .items-table th {
            background: #78350f;
            color: #fef3c7;
            padding: 14px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .items-table td {
            padding: 14px;
            border-bottom: 1px solid #fde68a;
        }

        .items-table tr:hover {
            background: #fffbeb;
        }

        .item-name {
            font-weight: bold;
            color: #78350f;
        }

        .item-description {
            color: #a16207;
            font-size: 10px;
            font-style: italic;
            margin-top: 4px;
        }

        .totals {
            margin-left: auto;
            width: 280px;
        }

        .totals-row {
            padding: 7px 12px;
            border-bottom: 1px solid #fde68a;
        }

        .totals-row table {
            width: 100%;
        }

        .totals-row.total {
            background: linear-gradient(135deg, #92400e 0%, #78350f 100%);
            color: #fef3c7;
            font-size: 16px;
            border-radius: 4px;
            border-bottom: none;
        }

        .notes {
            margin-top: 50px;
            padding: 25px;
            background: #fffbeb;
            border-left: 4px solid #d97706;
            border-radius: 0 8px 8px 0;
        }

        .notes-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #92400e;
            margin-bottom: 10px;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            color: #a16207;
            font-size: 11px;
            font-style: italic;
            padding-top: 20px;
            border-top: 1px solid #fde68a;
        }

        /* PAID Watermark Stamp */
        .paid-stamp {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 120px;
            font-weight: bold;
            color: rgba(217, 119, 6, 0.12);
            text-transform: uppercase;
            letter-spacing: 20px;
            pointer-events: none;
            z-index: 1000;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    @if($invoice->status === 'paid')
        <div class="paid-stamp">PAID</div>
    @endif
    <div class="container">
        <div class="header">
            @if($invoice->company->logo_data_uri)
                <img src="{{ $invoice->company->logo_data_uri }}" class="logo" alt="">
            @endif
            <div class="invoice-title">Invoice</div>
            <div class="invoice-number">No. {{ $invoice->invoice_number }}</div>
        </div>

        <div class="addresses">
            <table>
                <tr>
                    <td>
                        <div class="address-label">From</div>
                        <div class="company-name">{{ $invoice->company->name }}</div>
                        @if($invoice->company->address)
                        <div>{{ $invoice->company->address }}</div>@endif
                        @if($invoice->company->city)
                            <div>{{ $invoice->company->city }}, {{ $invoice->company->state }}
                        {{ $invoice->company->postal_code }}</div>@endif
                        @if($invoice->company->email)
                        <div>{{ $invoice->company->email }}</div>@endif
                    </td>
                    <td>
                        <div class="address-label">To</div>
                        <div class="company-name">{{ $invoice->client->bill_to_name }}</div>
                        @if($invoice->client->bill_to_contact)
                        <div>{{ $invoice->client->bill_to_contact }}</div>
                        @endif
                        @if($invoice->client->address)
                        <div>{{ $invoice->client->address }}</div>@endif
                        @if($invoice->client->city)
                            <div>{{ $invoice->client->city }}, {{ $invoice->client->state }}
                        {{ $invoice->client->postal_code }}</div>@endif
                        @if($invoice->client->email)
                        <div>{{ $invoice->client->email }}</div>@endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="meta-section">
            <table>
                <tr>
                    <td>
                        <div class="meta-label">Invoice Date</div>
                        <div class="meta-value">{{ $invoice->issue_date->format('F d, Y') }}</div>
                    </td>
                    <td>
                        <div class="meta-label">Due Date</div>
                        <div class="meta-value">{{ $invoice->due_date->format('F d, Y') }}</div>
                    </td>
                    <td>
                        <div class="meta-label">Amount Due</div>
                        <div class="meta-value">
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</div>
                    </td>
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
                @foreach($invoice->items as $item)
                    <tr>
                        <td>
                            <div class="item-name">{{ $item->name }}</div>
                            @if($item->description)
                            <div class="item-description">{{ $item->description }}</div>@endif
                        </td>
                        <td style="text-align: center;">{{ $item->quantity }} {{ $item->unit }}</td>
                        <td style="text-align: right;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                        <td style="text-align: right;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <table>
                    <tr>
                        <td>Subtotal</td>
                        <td style="text-align: right;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                </table>
            </div>
            <div class="totals-row">
                <table>
                    <tr>
                        <td>Tax</td>
                        <td style="text-align: right;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
            @if($invoice->discount_amount > 0)
                <div class="totals-row">
                    <table>
                        <tr>
                            <td>Discount</td>
                            <td style="text-align: right;">
                                -{{ $invoice->company->currency_symbol }}{{ number_format($invoice->discount_type === 'percentage' ? ($invoice->subtotal * $invoice->discount_amount / 100) : $invoice->discount_amount, 2) }}
                            </td>
                        </tr>
                    </table>
                </div>
            @endif
            <div class="totals-row total">
                <table>
                    <tr>
                        <td>Total</td>
                        <td style="text-align: right;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($invoice->notes || $invoice->terms)
            <div class="notes">
                @if($invoice->notes)
                    <div class="notes-title">Notes</div>
                <div style="margin-bottom: 15px;">{{ $invoice->notes }}</div>@endif
                @if($invoice->terms)
                    <div class="notes-title">Terms</div>
                <div>{{ $invoice->terms }}</div>@endif
            </div>
        @endif

        @include('invoices.pdf.partials.custom-fields', ['doc' => $invoice, 'entity' => 'invoice'])
        @include('invoices.pdf.partials.signature')

        @if($invoice->company->settings->show_invoice_footer ?? true)
            <div class="footer">{{ $invoice->company->invoice_footer ?? 'Thank you for your business' }}</div>
        @endif
    </div>
</body>

</html>