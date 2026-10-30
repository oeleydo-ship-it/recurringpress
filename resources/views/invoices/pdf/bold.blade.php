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
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #111;
            line-height: 1.5;
        }

        .container {
            padding: 0;
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
        }

        /* Header */
        .header {
            background: #111;
            color: #fff;
            padding: 40px;
            display: table;
            width: 100%;
        }

        .header-content {
            display: table-cell;
            vertical-align: middle;
        }

        .logo {
            max-height: 60px;
            margin-bottom: 20px;
            filter: brightness(0) invert(1);
        }

        /* Make logo white */
        .company-name-header {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .invoice-details {
            text-align: right;
            display: table-cell;
            vertical-align: middle;
        }

        .invoice-title {
            font-size: 48px;
            font-weight: 900;
            letter-spacing: -2px;
            line-height: 1;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .invoice-number {
            font-size: 16px;
            font-weight: 500;
            opacity: 0.7;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #fff;
            color: #000;
            font-weight: bold;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 10px;
            text-transform: uppercase;
        }

        /* Content */
        .content {
            padding: 40px;
        }

        /* Addresses */
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 18px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }

        .address-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .address-label {
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            color: #666;
        }

        .name {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .address-line {
            color: #444;
        }

        /* Meta Grid */
        .meta-grid {
            display: table;
            width: 100%;
            margin-bottom: 18px;
            background: #f4f4f4;
            border-radius: 8px;
        }

        .meta-item {
            display: table-cell;
            width: 25%;
            padding: 8px;
            text-align: center;
            border-right: 1px solid #ddd;
        }

        .meta-item:last-child {
            border-right: none;
        }

        .meta-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
        }

        .meta-value {
            font-size: 14px;
            font-weight: 900;
            color: #000;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .items-table th {
            background: #000;
            color: #fff;
            text-align: left;
            padding: 10px;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .items-table tr:last-child td {
            border-bottom: 2px solid #000;
        }

        .item-name {
            font-weight: 800;
            font-size: 13px;
            margin-bottom: 2px;
        }

        .item-desc {
            font-size: 11px;
            color: #666;
        }

        /* Totals */
        .totals-section {
            float: right;
            width: 300px;
        }

        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .total-row.final {
            border-bottom: none;
            border-top: 4px solid #000;
            padding-top: 15px;
            margin-top: 15px;
        }

        .total-label {
            display: table-cell;
            font-weight: 700;
            color: #666;
            width: 50%;
        }

        .total-value {
            display: table-cell;
            text-align: right;
            font-weight: 900;
            width: 50%;
        }

        .total-row.final .total-value {
            font-size: 20px;
        }

        /* Footer */
        .footer {
            clear: both;
            margin-top: 60px;
            padding-top: 14px;
            text-align: center;
            font-size: 11px;
            color: #888;
            border-top: 1px solid #eee;
        }

        /* Notes */
        .notes {
            background: #000;
            color: #fff;
            padding: 30px;
            margin-top: 18px;
            border-radius: 8px;
            clear: both;
        }

        .notes-title {
            font-weight: 900;
            text-transform: uppercase;
            font-size: 11px;
            margin-bottom: 10px;
            letter-spacing: 1px;
            opacity: 0.8;
        }

        .notes-text {
            opacity: 0.8;
        }

        .paid-stamp {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            font-size: 100px;
            font-weight: 900;
            color: rgba(0, 0, 0, 0.05);
            text-transform: uppercase;
            letter-spacing: 10px;
            pointer-events: none;
            z-index: 1000;
            border: 10px solid rgba(0, 0, 0, 0.05);
            padding: 20px 40px;
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
                @if($invoice->company->logo_data_uri)
                    <img src="{{ $invoice->company->logo_data_uri }}" class="logo"
                        alt="{{ $invoice->company->name }}">
                @else
                    <div class="company-name-header">{{ $invoice->company->name }}</div>
                @endif
            </div>
            <div class="invoice-details">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
                <span class="status-badge">{{ strtoupper($invoice->status) }}</span>
            </div>
        </div>

        <div class="content">
            <div class="addresses">
                <div class="address-col">
                    <div class="address-label">From</div>
                    <div class="name">{{ $invoice->company->name }}</div>
                    @if($invoice->company->address)
                    <div class="address-line">{{ $invoice->company->address }}</div>@endif
                    @if($invoice->company->city)
                        <div class="address-line">{{ $invoice->company->city }}, {{ $invoice->company->state }}
                    {{ $invoice->company->postal_code }}</div>@endif
                    @if($invoice->company->email)
                    <div class="address-line">{{ $invoice->company->email }}</div>@endif
                </div>
                <div class="address-col">
                    <div class="address-label">Bill To</div>
                    <div class="name">{{ $invoice->client->bill_to_name }}</div>
                    @if($invoice->client->bill_to_contact)
                    <div class="address-line">{{ $invoice->client->bill_to_contact }}</div>
                    @endif
                    @if($invoice->client->address)
                    <div class="address-line">{{ $invoice->client->address }}</div>@endif
                    @if($invoice->client->city)
                        <div class="address-line">{{ $invoice->client->city }}, {{ $invoice->client->state }}
                    {{ $invoice->client->postal_code }}</div>@endif
                    @if($invoice->client->email)
                    <div class="address-line">{{ $invoice->client->email }}</div>@endif
                </div>
            </div>

            <div class="meta-grid">
                <div class="meta-item">
                    <div class="meta-label">Date</div>
                    <div class="meta-value">{{ $invoice->issue_date->format('M d, Y') }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Due Date</div>
                    <div class="meta-value">{{ $invoice->due_date->format('M d, Y') }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Reference</div>
                    <div class="meta-value">{{ $invoice->invoice_number }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Amount Due</div>
                    <div class="meta-value">
                        {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</div>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th width="50%">Description</th>
                        <th width="15%" style="text-align: center;">Qty</th>
                        <th width="15%" style="text-align: right;">Price</th>
                        <th width="20%" style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>
                                <div class="item-name">{{ $item->name }}</div>
                                @if($item->description)
                                <div class="item-desc">{{ $item->description }}</div>@endif
                            </td>
                            <td style="text-align: center;">{{ $item->quantity }}</td>
                            <td style="text-align: right;">
                                {{ $invoice->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                            <td style="text-align: right; font-weight: bold;">
                                {{ $invoice->company->currency_symbol }}{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals-section">
                <div class="total-row">
                    <div class="total-label">Subtotal</div>
                    <div class="total-value">
                        {{ $invoice->company->currency_symbol }}{{ number_format($invoice->subtotal, 2) }}</div>
                </div>
                <div class="total-row">
                    <div class="total-label">Tax</div>
                    <div class="total-value">
                        {{ $invoice->company->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</div>
                </div>
                @if($invoice->discount_amount > 0)
                    <div class="total-row">
                        <div class="total-label">Discount</div>
                        <div class="total-value">
                            -{{ $invoice->company->currency_symbol }}{{ number_format($invoice->discount_type === 'percentage' ? ($invoice->subtotal * $invoice->discount_amount / 100) : $invoice->discount_amount, 2) }}
                        </div>
                    </div>
                @endif
                <div class="total-row final">
                    <div class="total-label">TOTAL</div>
                    <div class="total-value">
                        {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</div>
                </div>
            </div>

            @if($invoice->notes || $invoice->terms)
                <div class="notes">
                    @if($invoice->notes)
                        <div style="margin-bottom: 15px;">
                            <div class="notes-title">Notes</div>
                            <div class="notes-text">{{ $invoice->notes }}</div>
                        </div>
                    @endif
                    @if($invoice->terms)
                        <div>
                            <div class="notes-title">Terms & Conditions</div>
                            <div class="notes-text">{{ $invoice->terms }}</div>
                        </div>
                    @endif
                </div>
            @endif

            @include('invoices.pdf.partials.custom-fields', ['doc' => $invoice, 'entity' => 'invoice'])
            @include('invoices.pdf.partials.signature')

            @if($invoice->company->invoice_footer && ($invoice->company->settings->show_invoice_footer ?? true))
                <div class="footer">
                    {{ $invoice->company->invoice_footer }}
                </div>
            @endif
        </div>
    </div>
</body>

</html>