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
            max-height: 60px;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .invoice-number {
            color: #666;
            margin-top: 5px;
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
            padding-right: 20px;
        }

        .address-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 8px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .info-box {
            background: #f9fafb;
            padding: 10px;
            margin-bottom: 14px;
            border-radius: 4px;
        }

        .info-box table {
            width: 100%;
        }

        .info-box td {
            padding: 5px 15px 5px 0;
        }

        .info-label {
            color: #666;
            font-size: 10px;
            text-transform: uppercase;
        }

        .info-value {
            font-weight: bold;
            font-size: 12px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .items-table th {
            background: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 2px solid #e5e7eb;
        }

        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table .text-right {
            text-align: right;
        }

        .item-name {
            font-weight: 600;
        }

        .item-description {
            color: #666;
            font-size: 11px;
            margin-top: 2px;
        }

        .totals {
            margin-left: auto;
            width: 250px;
        }

        .totals-row {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals-row table {
            width: 100%;
        }

        .totals-row.total {
            border-bottom: none;
            border-top: 2px solid #333;
            font-size: 16px;
            font-weight: bold;
            padding-top: 12px;
        }

        .notes {
            margin-top: 18px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .notes-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .notes-content {
            color: #666;
        }

        .footer {
            margin-top: 18px;
            text-align: center;
            color: #999;
            font-size: 10px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
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

        .status-partial {
            background: #fef3c7;
            color: #d97706;
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
                    <td style="width: 60%;">
                        @if($invoice->company->logo_data_uri)
                            <img src="{{ $invoice->company->logo_data_uri }}" class="logo" alt="">
                        @else
                            <div class="company-name" style="font-size: 20px;">{{ $invoice->company->name }}</div>
                        @endif
                    </td>
                    <td style="width: 40%; text-align: right;">
                        <div class="invoice-title">INVOICE</div>
                        <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
                        <div style="margin-top: 10px;">
                            <span
                                class="status-badge status-{{ $invoice->status }}">{{ strtoupper($invoice->status) }}</span>
                        </div>
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
                                {{ $invoice->company->postal_code }}
                        </div>@endif
                        @if($invoice->company->email)
                        <div>{{ $invoice->company->email }}</div>@endif
                        @if($invoice->company->phone)
                        <div>{{ $invoice->company->phone }}</div>@endif
                    </td>
                    <td>
                        <div class="address-label">Bill To</div>
                        <div class="company-name">{{ $invoice->client->bill_to_name }}</div>
                        @if($invoice->client->bill_to_contact)
                        <div>{{ $invoice->client->bill_to_contact }}</div>
                        @endif
                        @if($invoice->client->address)
                        <div>{{ $invoice->client->address }}</div>@endif
                        @if($invoice->client->city)
                            <div>{{ $invoice->client->city }}, {{ $invoice->client->state }}
                                {{ $invoice->client->postal_code }}
                        </div>@endif
                        @if($invoice->client->email)
                        <div>{{ $invoice->client->email }}</div>@endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="info-box">
            <table>
                <tr>
                    <td>
                        <div class="info-label">Invoice Number</div>
                        <div class="info-value">{{ $invoice->invoice_number }}</div>
                    </td>
                    <td>
                        <div class="info-label">Issue Date</div>
                        <div class="info-value">{{ $invoice->issue_date->format('M d, Y') }}</div>
                    </td>
                    <td>
                        <div class="info-label">Due Date</div>
                        <div class="info-value">{{ $invoice->due_date->format('M d, Y') }}</div>
                    </td>
                    <td>
                        <div class="info-label">Amount Due</div>
                        <div class="info-value">
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}
                        </div>
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
                @foreach($invoice->items as $item)
                    <tr>
                        <td>
                            <div class="item-name">{{ $item->name }}</div>
                            @if($item->description)
                            <div class="item-description">{{ $item->description }}</div>@endif
                        </td>
                        <td style="text-align: center;">{{ $item->quantity }} {{ $item->unit }}</td>
                        <td style="text-align: right;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($item->price, 2) }}
                        </td>
                        <td style="text-align: right;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($item->subtotal, 2) }}
                        </td>
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
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->subtotal, 2) }}
                        </td>
                    </tr>
                </table>
            </div>
            <div class="totals-row">
                <table>
                    <tr>
                        <td>Tax</td>
                        <td style="text-align: right;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}
                        </td>
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
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        @if($invoice->notes || $invoice->terms)
            <div class="notes">
                @if($invoice->notes)
                    <div style="margin-bottom: 15px;">
                        <div class="notes-title">Notes</div>
                        <div class="notes-content">{{ $invoice->notes }}</div>
                    </div>
                @endif
                @if($invoice->terms)
                    <div>
                        <div class="notes-title">Terms & Conditions</div>
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