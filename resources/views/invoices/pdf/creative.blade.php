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
            line-height: 1.6;
        }

        .container {
            padding: 0;
        }

        .header {
            background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 50%, #6366f1 100%);
            color: white;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(15deg);
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .logo {
            max-height: 40px;
            margin-bottom: 20px;
        }

        .invoice-title {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -2px;
        }

        .invoice-number {
            opacity: 0.9;
            font-size: 16px;
            margin-top: 5px;
        }

        .content {
            padding: 40px;
        }

        .info-cards {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }

        .info-card {
            display: table-cell;
            width: 25%;
            padding: 10px;
            background: linear-gradient(135deg, #fdf4ff 0%, #faf5ff 100%);
            border-radius: 12px;
            margin-right: 10px;
        }

        .info-card:last-child {
            margin-right: 0;
        }

        .info-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #a855f7;
            font-weight: 600;
        }

        .info-value {
            font-size: 14px;
            font-weight: 700;
            color: #581c87;
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
            padding-right: 30px;
        }

        .address-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #a855f7;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .company-name {
            font-size: 16px;
            font-weight: 700;
            color: #581c87;
            margin-bottom: 5px;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
            margin-bottom: 14px;
        }

        .items-table th {
            background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
            color: white;
            padding: 14px 16px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .items-table th:first-child {
            border-radius: 8px 0 0 8px;
        }

        .items-table th:last-child {
            border-radius: 0 8px 8px 0;
        }

        .items-table td {
            padding: 16px;
            background: #faf5ff;
        }

        .items-table tr td:first-child {
            border-radius: 8px 0 0 8px;
        }

        .items-table tr td:last-child {
            border-radius: 0 8px 8px 0;
        }

        .item-name {
            font-weight: 600;
            color: #581c87;
        }

        .item-description {
            color: #9333ea;
            font-size: 10px;
            margin-top: 3px;
        }

        .totals {
            margin-left: auto;
            width: 280px;
        }

        .totals-row {
            padding: 10px 16px;
            margin-bottom: 4px;
            background: #faf5ff;
            border-radius: 6px;
        }

        .totals-row table {
            width: 100%;
        }

        .totals-row.total {
            background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
            color: white;
            font-size: 16px;
            font-weight: 700;
            border-radius: 10px;
        }

        .notes {
            margin-top: 18px;
            padding: 8px;
            background: linear-gradient(135deg, #fdf4ff 0%, #faf5ff 100%);
            border-radius: 12px;
            border-left: 4px solid #a855f7;
        }

        .notes-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #a855f7;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .footer {
            margin-top: 18px;
            text-align: center;
            padding: 8px;
            color: #9333ea;
            font-size: 11px;
        }

        /* PAID Watermark Stamp */
        .paid-stamp {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 120px;
            font-weight: bold;
            color: rgba(139, 92, 246, 0.12);
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
            <div class="header-content">
                @if($invoice->company->logo)
                    <img src="{{ public_path('storage/' . $invoice->company->logo) }}" class="logo" alt="">
                @else
                    <div style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">{{ $invoice->company->name }}</div>
                @endif
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
            </div>
        </div>

        <div class="content">
            <div class="info-cards">
                <div class="info-card">
                    <div class="info-label">Invoice No.</div>
                    <div class="info-value">{{ $invoice->invoice_number }}</div>
                </div>
                <div class="info-card">
                    <div class="info-label">Issue Date</div>
                    <div class="info-value">{{ $invoice->issue_date->format('M d, Y') }}</div>
                </div>
                <div class="info-card">
                    <div class="info-label">Due Date</div>
                    <div class="info-value">{{ $invoice->due_date->format('M d, Y') }}</div>
                </div>
                <div class="info-card">
                    <div class="info-label">Total Due</div>
                    <div class="info-value">
                        {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</div>
                </div>
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
                        </td>
                    </tr>
                </table>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 45%;">Item</th>
                        <th style="width: 15%; text-align: center;">Qty</th>
                        <th style="width: 20%; text-align: right;">Rate</th>
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
                            <td style="text-align: center;">{{ $item->quantity }}</td>
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

            @include('invoices.pdf.partials.signature')

            @if($invoice->company->settings->show_invoice_footer ?? true)
                <div class="footer">{{ $invoice->company->invoice_footer ?? 'Thank you for your business!' }}</div>
            @endif
        </div>
    </div>
</body>

</html>