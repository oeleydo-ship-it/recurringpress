<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    @php $color = $invoice->company->settings->primary_color ?? '#4f46e5'; @endphp
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
            color: #1f2937;
            line-height: 1.6;
        }

        .header {
            background:
                {{ $color }}
            ;
            color: white;
            padding: 40px;
        }

        .header table {
            width: 100%;
        }

        .logo {
            max-height: 50px;
        }

        .invoice-title {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .invoice-number {
            opacity: 0.8;
            font-size: 14px;
            margin-top: 5px;
        }

        .content {
            padding: 40px;
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
            font-size: 11px;
            text-transform: uppercase;
            color:
                {{ $color }}
            ;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .company-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .meta-grid {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }

        .meta-item {
            display: table-cell;
            padding: 8px;
            background: #f9fafb;
            border-right: 3px solid white;
        }

        .meta-item:last-child {
            border-right: none;
        }

        .meta-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 600;
        }

        .meta-value {
            font-size: 16px;
            font-weight: 700;
            margin-top: 5px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .items-table th {
            background:
                {{ $color }}
            ;
            color: white;
            padding: 14px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 16px 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .item-name {
            font-weight: 600;
            font-size: 13px;
        }

        .item-description {
            color: #6b7280;
            font-size: 11px;
            margin-top: 4px;
        }

        .totals {
            margin-left: auto;
            width: 280px;
            background: #f9fafb;
            border-radius: 8px;
            overflow: hidden;
        }

        .totals-row {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals-row table {
            width: 100%;
        }

        .totals-row.total {
            background:
                {{ $color }}
            ;
            color: white;
            font-size: 18px;
            font-weight: 700;
            border-bottom: none;
        }

        .notes {
            margin-top: 18px;
            padding: 8px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .notes-title {
            font-size: 11px;
            text-transform: uppercase;
            color:
                {{ $color }}
            ;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .footer {
            margin-top: 18px;
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            background: rgba(255, 255, 255, 0.2);
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
    <div class="header">
        <table>
            <tr>
                <td style="width: 60%;">
                    @if($invoice->company->logo_data_uri)
                        <img src="{{ $invoice->company->logo_data_uri }}" class="logo" alt="">
                    @else
                        <div style="font-size: 24px; font-weight: 700;">{{ $invoice->company->name }}</div>
                    @endif
                </td>
                <td style="width: 40%; text-align: right;">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
                    <div style="margin-top: 10px;"><span class="status-badge">{{ strtoupper($invoice->status) }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
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

        <div class="meta-grid">
            <div class="meta-item">
                <div class="meta-label">Invoice Number</div>
                <div class="meta-value">{{ $invoice->invoice_number }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Issue Date</div>
                <div class="meta-value">{{ $invoice->issue_date->format('M d, Y') }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Due Date</div>
                <div class="meta-value">{{ $invoice->due_date->format('M d, Y') }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Total Due</div>
                <div class="meta-value">{{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}
                </div>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Description</th>
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
                        <td style="text-align: right; font-weight: 600;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                </table>
            </div>
            <div class="totals-row">
                <table>
                    <tr>
                        <td>Tax</td>
                        <td style="text-align: right; font-weight: 600;">
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
            @if($invoice->discount_amount > 0)
                <div class="totals-row">
                    <table>
                        <tr>
                            <td>Discount</td>
                            <td style="text-align: right; font-weight: 600;">
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

        @include('invoices.pdf.partials.signature')

        @if($invoice->company->invoice_footer && ($invoice->company->settings->show_invoice_footer ?? true))
            <div class="footer">{{ $invoice->company->invoice_footer }}</div>
        @endif
    </div>
</body>

</html>