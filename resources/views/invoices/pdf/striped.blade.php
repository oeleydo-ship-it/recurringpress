<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    @php $accent = '#10b981'; @endphp
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
            font-family: 'Verdana', sans-serif;
            font-size: 13px;
            color: #374151;
        }

        .container {
            padding: 40px;
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
        }

        .header {
            margin-bottom: 18px;
            display: table;
            width: 100%;
            border-bottom: 2px solid
                {{ $accent }}
            ;
            padding-bottom: 20px;
        }

        .logo-col {
            display: table-cell;
            vertical-align: bottom;
        }

        .title-col {
            display: table-cell;
            text-align: right;
            vertical-align: bottom;
        }

        .logo {
            max-height: 50px;
        }

        .invoice-title {
            font-size: 32px;
            color:
                {{ $accent }}
            ;
            letter-spacing: -1px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .invoice-subtitle {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 5px;
        }

        .big-number {
            font-size: 40px;
            font-weight: bold;
            color: #f3f4f6;
            position: absolute;
            top: 40px;
            right: 40px;
            z-index: 0;
        }

        .info-bar {
            background: #f9fafb;
            border-left: 4px solid
                {{ $accent }}
            ;
            padding: 10px;
            display: table;
            width: 100%;
            margin-bottom: 14px;
        }

        .info-col {
            display: table-cell;
            padding: 0 15px;
            border-right: 1px solid #e5e7eb;
        }

        .info-col:last-child {
            border-right: none;
        }

        .info-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #9ca3af;
            margin-bottom: 3px;
            font-weight: bold;
        }

        .info-value {
            font-weight: bold;
            color: #111827;
        }

        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }

        .addr-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }

        .addr-header {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 10px;
            font-weight: bold;
            color:
                {{ $accent }}
            ;
            text-transform: uppercase;
            font-size: 11px;
        }

        .company-name {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 14px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .items-table th {
            background:
                {{ $accent }}
            ;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }

        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .items-table .amount {
            font-weight: bold;
        }

        .summary-section {
            display: table;
            width: 100%;
        }

        .notes-col {
            display: table-cell;
            width: 60%;
            vertical-align: top;
            padding-right: 20px;
        }

        .totals-col {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals-table .total-row td {
            border-bottom: none;
            border-top: 2px solid
                {{ $accent }}
            ;
            font-size: 16px;
            font-weight: bold;
            color:
                {{ $accent }}
            ;
            padding-top: 15px;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }

        .paid-stamp {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 100px;
            font-weight: bold;
            color:
                {{ $accent }}
            ;
            opacity: 0.15;
            border: 8px solid
                {{ $accent }}
            ;
            padding: 8px;
        }
    </style>
</head>

<body>
    @if($invoice->status === 'paid')
        <div class="paid-stamp">PAID</div>
    @endif

    <div class="container">
        <div class="header">
            <div class="logo-col">
                @if($invoice->company->logo)
                    <img src="{{ public_path('storage/' . $invoice->company->logo) }}" class="logo">
                @else
                    <h2>{{ $invoice->company->name }}</h2>
                @endif
            </div>
            <div class="title-col">
                <div class="invoice-title">Invoice</div>
                <div class="invoice-subtitle">#{{ $invoice->invoice_number }}</div>
            </div>
        </div>

        <div class="addresses">
            <div class="addr-col">
                <div class="addr-header">From</div>
                <div class="company-name">{{ $invoice->company->name }}</div>
                @if($invoice->company->address)
                <div>{{ $invoice->company->address }}</div>@endif
                @if($invoice->company->city)
                    <div>{{ $invoice->company->city }}, {{ $invoice->company->state }} {{ $invoice->company->postal_code }}
                </div>@endif
                @if($invoice->company->email)
                <div>{{ $invoice->company->email }}</div>@endif
            </div>
            <div class="addr-col">
                <div class="addr-header">Bill To</div>
                <div class="company-name">{{ $invoice->client->bill_to_name }}</div>
                @if($invoice->client->bill_to_contact)
                <div>{{ $invoice->client->bill_to_contact }}</div>
                @endif
                @if($invoice->client->address)
                <div>{{ $invoice->client->address }}</div>@endif
                @if($invoice->client->city)
                    <div>{{ $invoice->client->city }}, {{ $invoice->client->state }} {{ $invoice->client->postal_code }}
                </div>@endif
                @if($invoice->client->email)
                <div>{{ $invoice->client->email }}</div>@endif
            </div>
        </div>

        <div class="info-bar">
            <div class="info-col">
                <div class="info-label">Issue Date</div>
                <div class="info-value">{{ $invoice->issue_date->format('M d, Y') }}</div>
            </div>
            <div class="info-col">
                <div class="info-label">Due Date</div>
                <div class="info-value">{{ $invoice->due_date->format('M d, Y') }}</div>
            </div>
            <div class="info-col">
                <div class="info-label">Status</div>
                <div class="info-value" style="color: {{ $accent }}">{{ strtoupper($invoice->status) }}</div>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="50%">Item</th>
                    <th width="15%" style="text-align: center">Qty</th>
                    <th width="15%" style="text-align: right">Price</th>
                    <th width="20%" style="text-align: right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>
                            <div style="font-weight: bold">{{ $item->name }}</div>
                            @if($item->description)
                                <div style="color: #6b7280; font-size: 11px;">{{ $item->description }}</div>
                            @endif
                        </td>
                        <td style="text-align: center">{{ $item->quantity }}</td>
                        <td style="text-align: right">
                            {{ $invoice->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                        <td style="text-align: right" class="amount">
                            {{ $invoice->company->currency_symbol }}{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-section">
            <div class="notes-col">
                @if($invoice->notes)
                    <div style="margin-bottom: 20px;">
                        <div style="font-weight: bold; margin-bottom: 5px; font-size: 12px;">Notes</div>
                        <div style="color: #6b7280; background: #f9fafb; padding: 10px; border-radius: 4px;">
                            {{ $invoice->notes }}</div>
                    </div>
                @endif

                @if($invoice->terms)
                    <div>
                        <div style="font-weight: bold; margin-bottom: 5px; font-size: 12px;">Terms & Conditions</div>
                        <div style="color: #6b7280; font-size: 11px;">{{ $invoice->terms }}</div>
                    </div>
                @endif
            </div>
            <div class="totals-col">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal</td>
                        <td style="text-align: right">
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Tax</td>
                        <td style="text-align: right">
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                        <tr>
                            <td>Discount</td>
                            <td style="text-align: right">
                                -{{ $invoice->company->currency_symbol }}{{ number_format($invoice->discount_type === 'percentage' ? ($invoice->subtotal * $invoice->discount_amount / 100) : $invoice->discount_amount, 2) }}
                            </td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total</td>
                        <td style="text-align: right">
                            {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @include('invoices.pdf.partials.signature')

        @if($invoice->company->invoice_footer && ($invoice->company->settings->show_invoice_footer ?? true))
            <div class="footer">
                {{ $invoice->company->invoice_footer }}
            </div>
        @endif
    </div>
</body>

</html>