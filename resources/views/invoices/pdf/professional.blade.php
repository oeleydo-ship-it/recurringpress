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
            color: #1e3a5f;
            line-height: 1.6;
        }

        .container {
            padding: 40px;
        }

        .header {
            margin-bottom: 18px;
        }

        .header table {
            width: 100%;
        }

        .logo {
            max-height: 50px;
        }

        .company-header {
            font-size: 20px;
            font-weight: 700;
            color: #1e3a5f;
        }

        .invoice-badge {
            display: inline-block;
            background: #1e3a5f;
            color: white;
            padding: 8px 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .divider {
            height: 3px;
            background: linear-gradient(90deg, #1e3a5f 0%, #3b82f6 100%);
            margin: 20px 0;
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

        .address-block {
            padding: 10px;
            background: #f0f9ff;
            border-left: 3px solid #1e3a5f;
        }

        .address-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #3b82f6;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .company-name {
            font-size: 14px;
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 5px;
        }

        .meta-bar {
            background: #1e3a5f;
            color: white;
            padding: 15px 20px;
            margin-bottom: 14px;
        }

        .meta-bar table {
            width: 100%;
        }

        .meta-bar td {
            padding: 0 15px;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .meta-bar td:last-child {
            border-right: none;
        }

        .meta-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
        }

        .meta-value {
            font-size: 14px;
            font-weight: 600;
            margin-top: 3px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .items-table th {
            background: #f0f9ff;
            color: #1e3a5f;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #1e3a5f;
        }

        .items-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e0f2fe;
        }

        .items-table tr:hover {
            background: #f0f9ff;
        }

        .item-name {
            font-weight: 600;
            color: #1e3a5f;
        }

        .item-description {
            color: #64748b;
            font-size: 10px;
            margin-top: 3px;
        }

        .totals {
            margin-left: auto;
            width: 280px;
            border: 2px solid #1e3a5f;
        }

        .totals-row {
            padding: 7px 12px;
            border-bottom: 1px solid #e0f2fe;
        }

        .totals-row table {
            width: 100%;
        }

        .totals-row.total {
            background: #1e3a5f;
            color: white;
            font-size: 15px;
            font-weight: 600;
            border-bottom: none;
        }

        .notes {
            margin-top: 18px;
        }

        .notes-box {
            padding: 8px;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
        }

        .notes-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #1e3a5f;
            margin-bottom: 10px;
            font-weight: 600;
            border-bottom: 1px solid #bae6fd;
            padding-bottom: 5px;
        }

        .footer {
            margin-top: 18px;
            text-align: center;
            padding: 8px;
            border-top: 3px solid #1e3a5f;
            color: #64748b;
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
            color: rgba(30, 58, 95, 0.12);
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
                    <td style="width: 60%;">
                        @if($invoice->company->logo_data_uri)
                            <img src="{{ $invoice->company->logo_data_uri }}" class="logo" alt="">
                        @else
                            <div class="company-header">{{ $invoice->company->name }}</div>
                        @endif
                    </td>
                    <td style="width: 40%; text-align: right;">
                        <span class="invoice-badge">Invoice</span>
                        <div style="margin-top: 10px; font-size: 14px; color: #64748b;">{{ $invoice->invoice_number }}
                        </div>
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
                            <div class="company-name">{{ $invoice->company->name }}</div>
                            @if($invoice->company->address)
                            <div>{{ $invoice->company->address }}</div>@endif
                            @if($invoice->company->city)
                                <div>{{ $invoice->company->city }}, {{ $invoice->company->state }}
                            {{ $invoice->company->postal_code }}</div>@endif
                            @if($invoice->company->email)
                            <div>{{ $invoice->company->email }}</div>@endif
                        </div>
                    </td>
                    <td style="padding-left: 15px;">
                        <div class="address-block">
                            <div class="address-label">Bill To</div>
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
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="meta-bar">
            <table>
                <tr>
                    <td>
                        <div class="meta-label">Invoice Number</div>
                        <div class="meta-value">{{ $invoice->invoice_number }}</div>
                    </td>
                    <td>
                        <div class="meta-label">Issue Date</div>
                        <div class="meta-value">{{ $invoice->issue_date->format('M d, Y') }}</div>
                    </td>
                    <td>
                        <div class="meta-label">Due Date</div>
                        <div class="meta-value">{{ $invoice->due_date->format('M d, Y') }}</div>
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
                    <th style="width: 15%; text-align: center;">Qty</th>
                    <th style="width: 20%; text-align: right;">Unit Price</th>
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
                        <td style="text-align: right; font-weight: 600;">
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
                        <td>Total Due</td>
                        <td style="text-align: right;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($invoice->notes || $invoice->terms)
            <div class="notes">
                <div class="notes-box">
                    @if($invoice->notes)
                        <div class="notes-title">Notes</div>
                    <div style="margin-bottom: 15px;">{{ $invoice->notes }}</div>@endif
                    @if($invoice->terms)
                        <div class="notes-title">Terms & Conditions</div>
                    <div>{{ $invoice->terms }}</div>@endif
                </div>
            </div>
        @endif

        @include('invoices.pdf.partials.signature')

        @if($invoice->company->settings->show_invoice_footer ?? true)
            <div class="footer">{{ $invoice->company->invoice_footer ?? 'Thank you for your business' }}</div>
        @endif
    </div>
</body>

</html>