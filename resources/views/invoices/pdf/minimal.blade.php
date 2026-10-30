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
            color: #374151;
            line-height: 1.7;
        }

        .container {
            padding: 50px;
            max-width: 700px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 50px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .header table {
            width: 100%;
        }

        .logo {
            max-height: 40px;
        }

        .invoice-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #9ca3af;
        }

        .invoice-number {
            font-size: 24px;
            font-weight: 300;
            margin-top: 5px;
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
        }

        .address-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #9ca3af;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .company-details {
            color: #6b7280;
        }

        .meta {
            margin-bottom: 18px;
        }

        .meta table {
            width: 100%;
        }

        .meta td {
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .meta-label {
            color: #9ca3af;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .items-table th {
            padding: 12px 0;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table td {
            padding: 16px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .item-name {
            font-weight: 500;
        }

        .item-description {
            color: #9ca3af;
            font-size: 10px;
            margin-top: 3px;
        }

        .totals {
            margin-left: auto;
            width: 220px;
        }

        .totals-row {
            padding: 8px 0;
        }

        .totals-row table {
            width: 100%;
        }

        .totals-row td {
            color: #6b7280;
        }

        .totals-row.total {
            padding-top: 15px;
            margin-top: 10px;
            border-top: 1px solid #e5e7eb;
        }

        .totals-row.total td {
            color: #111827;
            font-size: 18px;
            font-weight: 600;
        }

        .notes {
            margin-top: 50px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
        }

        .notes-title {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #9ca3af;
            margin-bottom: 10px;
        }

        .notes-content {
            color: #6b7280;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            color: #9ca3af;
            font-size: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-paid {
            background: #d1fae5;
            color: #059669;
        }

        .status-sent {
            background: #dbeafe;
            color: #2563eb;
        }

        .status-draft {
            background: #f3f4f6;
            color: #6b7280;
        }

        .status-overdue {
            background: #fee2e2;
            color: #dc2626;
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
                    <td style="width: 50%;">
                        @if($invoice->company->logo_data_uri)
                            <img src="{{ $invoice->company->logo_data_uri }}" class="logo" alt="">
                        @else
                            <div style="font-size: 16px; font-weight: 600;">{{ $invoice->company->name }}</div>
                        @endif
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <div class="invoice-title">Invoice</div>
                        <div class="invoice-number">{{ $invoice->invoice_number }}</div>
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
                        <div class="company-details">
                            @if($invoice->company->address){{ $invoice->company->address }}<br>@endif
                            @if($invoice->company->city){{ $invoice->company->city }}, {{ $invoice->company->state }}
                            {{ $invoice->company->postal_code }}<br>@endif
                            @if($invoice->company->email){{ $invoice->company->email }}@endif
                        </div>
                    </td>
                    <td>
                        <div class="address-label">To</div>
                        <div class="company-name">{{ $invoice->client->bill_to_name }}</div>
                        @if($invoice->client->bill_to_contact)
                        <div>{{ $invoice->client->bill_to_contact }}</div>
                        @endif
                        <div class="company-details">
                            @if($invoice->client->address){{ $invoice->client->address }}<br>@endif
                            @if($invoice->client->city){{ $invoice->client->city }}, {{ $invoice->client->state }}
                            {{ $invoice->client->postal_code }}<br>@endif
                            @if($invoice->client->email){{ $invoice->client->email }}@endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="meta">
            <table>
                <tr>
                    <td style="width: 25%;"><span class="meta-label">Invoice Date</span></td>
                    <td style="width: 25%;">{{ $invoice->issue_date->format('M d, Y') }}</td>
                    <td style="width: 25%;"><span class="meta-label">Due Date</span></td>
                    <td style="width: 25%;">{{ $invoice->due_date->format('M d, Y') }}</td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 15%; text-align: center;">Qty</th>
                    <th style="width: 17%; text-align: right;">Rate</th>
                    <th style="width: 18%; text-align: right;">Amount</th>
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
                        <td style="text-align: center;">{{ $item->quantity }}</td>
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
                    <div style="margin-bottom: 20px;">
                        <div class="notes-title">Notes</div>
                        <div class="notes-content">{{ $invoice->notes }}</div>
                    </div>
                @endif
                @if($invoice->terms)
                    <div>
                        <div class="notes-title">Terms</div>
                        <div class="notes-content">{{ $invoice->terms }}</div>
                    </div>
                @endif
            </div>
        @endif

        @include('invoices.pdf.partials.signature')

        @if($invoice->company->invoice_footer && ($invoice->company->settings->show_invoice_footer ?? true))
            <div class="footer">{{ $invoice->company->invoice_footer }}</div>
        @endif
    </div>
</body>

</html>