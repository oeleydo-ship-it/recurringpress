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
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }

        .container {
            padding: 40px;
        }

        .header {
            margin-bottom: 14px;
        }

        .header table {
            width: 100%;
        }

        .logo {
            max-height: 40px;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
        }

        .invoice-number {
            color: #666;
        }

        .addresses {
            margin-bottom: 14px;
        }

        .addresses table {
            width: 100%;
        }

        .addresses td {
            width: 50%;
            vertical-align: top;
        }

        .address-label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
        }

        .company-name {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .meta {
            margin-bottom: 14px;
            padding: 10px;
            background: #f5f5f5;
        }

        .meta table {
            width: 100%;
        }

        .meta td {
            padding: 5px 15px 5px 0;
        }

        .meta-label {
            color: #666;
            font-size: 11px;
        }

        .meta-value {
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .items-table th {
            background: #333;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .item-description {
            color: #666;
            font-size: 11px;
        }

        .totals {
            margin-left: auto;
            width: 200px;
        }

        .totals-row {
            padding: 5px 0;
        }

        .totals-row table {
            width: 100%;
        }

        .totals-row.total {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 14px;
            padding-top: 10px;
        }

        .notes {
            margin-top: 14px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
        }

        .footer {
            margin-top: 14px;
            text-align: center;
            color: #999;
            font-size: 10px;
        }

        /* PAID Watermark Stamp */
        .paid-stamp {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 120px;
            font-weight: bold;
            color: rgba(5, 150, 105, 0.12);
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
            <table>
                <tr>
                    <td>
                        @if($invoice->company->logo_data_uri)
                            <img src="{{ $invoice->company->logo_data_uri }}" class="logo" alt="">
                        @else
                            <div style="font-weight: bold; font-size: 16px;">{{ $invoice->company->name }}</div>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div class="invoice-title">INVOICE</div>
                        <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
                    </td>
                </tr>
            </table>
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

        <div class="meta">
            <table>
                <tr>
                    <td><span class="meta-label">Invoice Number:</span> <span
                            class="meta-value">{{ $invoice->invoice_number }}</span></td>
                    <td><span class="meta-label">Date:</span> <span
                            class="meta-value">{{ $invoice->issue_date->format('M d, Y') }}</span></td>
                    <td><span class="meta-label">Due:</span> <span
                            class="meta-value">{{ $invoice->due_date->format('M d, Y') }}</span></td>
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
                @foreach($invoice->items as $item)
                    <tr>
                        <td>
                            {{ $item->name }}
                            @if($item->description)
                            <div class="item-description">{{ $item->description }}</div>@endif
                        </td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                        <td style="text-align: right;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($item->total, 2) }}</td>
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
            @if($invoice->tax_amount > 0)
                <div class="totals-row">
                    <table>
                        <tr>
                            <td>Tax</td>
                            <td style="text-align: right;">
                                {{ $invoice->company->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</td>
                        </tr>
                    </table>
                </div>
            @endif
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
                <div style="margin-bottom: 10px;">{{ $invoice->notes }}</div>@endif
                @if($invoice->terms)
                    <div class="notes-title">Terms</div>
                <div>{{ $invoice->terms }}</div>@endif
            </div>
        @endif

        @include('invoices.pdf.partials.signature')

        @if($invoice->company->invoice_footer && ($invoice->company->settings->show_invoice_footer ?? true))
            <div class="footer">{{ $invoice->company->invoice_footer }}</div>
        @endif
    </div>
</body>

</html>