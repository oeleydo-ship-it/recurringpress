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
            font-family: 'Times New Roman', Times, serif;
            font-size: 13px;
            color: #4a4a4a;
            background: #fff;
        }

        .container {
            padding: 60px;
            max-width: 100%;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 60px;
        }

        .logo {
            max-height: 80px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 24px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #cca35e;
            margin-bottom: 5px;
        }

        .company-details {
            font-style: italic;
            color: #888;
        }

        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #cca35e, transparent);
            margin: 30px 0;
            opacity: 0.5;
        }

        .content-grid {
            display: table;
            width: 100%;
            margin-bottom: 50px;
        }

        .client-col {
            display: table-cell;
            width: 40%;
            vertical-align: middle;
        }

        .invoice-col {
            display: table-cell;
            width: 20%;
            vertical-align: middle;
            text-align: center;
        }

        .data-col {
            display: table-cell;
            width: 40%;
            vertical-align: middle;
            text-align: right;
        }

        .label {
            font-family: 'Helvetica', sans-serif;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #cca35e;
            margin-bottom: 10px;
        }

        .client-name {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .invoice-badge {
            border: 1px solid #cca35e;
            padding: 10px 20px;
            display: inline-block;
            color: #cca35e;
            font-size: 14px;
            letter-spacing: 3px;
        }

        .table-container {
            margin-bottom: 18px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            border-bottom: 1px solid #cca35e;
            border-top: 1px solid #cca35e;
            padding: 15px 0;
            text-align: center;
            font-family: 'Helvetica', sans-serif;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            color: #4a4a4a;
            font-weight: normal;
        }

        .items-table th:first-child {
            text-align: left;
        }

        .items-table th:last-child {
            text-align: right;
        }

        .items-table td {
            padding: 20px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .items-table td.desc-cell {
            padding-right: 20px;
        }

        .item-title {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .item-sub {
            font-style: italic;
            color: #888;
            font-size: 11px;
        }

        .total-block {
            float: right;
            width: 300px;
            text-align: right;
        }

        .row {
            margin-bottom: 10px;
            display: block;
        }

        .row-label {
            display: inline-block;
            width: 100px;
            font-family: 'Helvetica', sans-serif;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
        }

        .row-val {
            display: inline-block;
            width: 120px;
            font-size: 14px;
        }

        .grand-total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }

        .grand-total .row-val {
            font-size: 20px;
            color: #cca35e;
        }

        .footer {
            clear: both;
            margin-top: 100px;
            text-align: center;
            font-size: 10px;
            color: #aaa;
            letter-spacing: 1px;
        }

        .paid-stamp {
            position: fixed;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 3px solid #cca35e;
            color: #cca35e;
            padding: 15px 40px;
            font-size: 40px;
            letter-spacing: 10px;
            opacity: 0.2;
            transform: rotate(-10deg);
            z-index: -1;
        }
    </style>
</head>

<body>
    @if($invoice->status === 'paid')
        <div class="paid-stamp">PAID</div>
    @endif

    <div class="container">
        <div class="header">
            @if($invoice->company->logo)
                <img src="{{ public_path('storage/' . $invoice->company->logo) }}" class="logo">
            @else
                <div class="company-name">{{ $invoice->company->name }}</div>
            @endif
            <div class="company-details">
                {{ $invoice->company->city }} @if($invoice->company->country), {{ $invoice->company->country }}@endif
            </div>
        </div>

        <div class="divider"></div>

        <div class="content-grid">
            <div class="client-col">
                <div class="label">PREPARED FOR</div>
                <div class="client-name">{{ $invoice->client->bill_to_name }}</div>
                @if($invoice->client->bill_to_contact)
                <div>{{ $invoice->client->bill_to_contact }}</div>
                @endif
                @if($invoice->client->email)
                <div>{{ $invoice->client->email }}</div>@endif
                @if($invoice->client->address)
                    <div>{{ $invoice->client->address }}</div>
                    <div>{{ $invoice->client->city }} {{ $invoice->client->postal_code }}</div>
                @endif
            </div>
            <div class="invoice-col">
                <div class="invoice-badge">INVOICE</div>
            </div>
            <div class="data-col">
                <div class="label">DETAILS</div>
                <div>No. {{ $invoice->invoice_number }}</div>
                <div>Issued: {{ $invoice->issue_date->format('M d, Y') }}</div>
                <div>Due: <span style="color: #cca35e">{{ $invoice->due_date->format('M d, Y') }}</span></div>
            </div>
        </div>

        <div class="table-container">
            <table class="items-table">
                <thead>
                    <tr>
                        <th width="50%">Description</th>
                        <th width="15%">Quantity</th>
                        <th width="20%">Rate</th>
                        <th width="15%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td class="desc-cell">
                                <div class="item-title">{{ $item->name }}</div>
                                @if($item->description)
                                <div class="item-sub">{{ $item->description }}</div>@endif
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
        </div>

        <div class="total-block">
            <div class="row">
                <span class="row-label">Subtotal</span>
                <span
                    class="row-val">{{ $invoice->company->currency_symbol }}{{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            <div class="row">
                <span class="row-label">Tax</span>
                <span
                    class="row-val">{{ $invoice->company->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</span>
            </div>
            @if($invoice->discount_amount > 0)
                <div class="row">
                    <span class="row-label">Discount</span>
                    <span
                        class="row-val">-{{ $invoice->company->currency_symbol }}{{ number_format($invoice->discount_type === 'percentage' ? ($invoice->subtotal * $invoice->discount_amount / 100) : $invoice->discount_amount, 2) }}</span>
                </div>
            @endif
            <div class="row grand-total">
                <span class="row-label">Total</span>
                <span
                    class="row-val">{{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</span>
            </div>
        </div>

        @include('invoices.pdf.partials.signature')

        @if($invoice->company->settings->show_invoice_footer ?? true)
        <div class="footer">
            {{ $invoice->company->invoice_footer ?? $invoice->company->name }}
            @if($invoice->notes) | {{ $invoice->notes }} @endif
        </div>
        @endif
    </div>
</body>

</html>