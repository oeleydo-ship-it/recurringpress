<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    @php $primary = '#2563eb';
    $dark = '#1e293b'; @endphp
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
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #333;
            background: #f8fafc;
        }

        .container {
            padding: 0;
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            min-height: 1000px;
        }

        .top-bar {
            height: 10px;
            background: repeating-linear-gradient(45deg,
                    {{ $primary }}
                    ,
                    {{ $primary }}
                    10px,
                    {{ $dark }}
                    10px,
                    {{ $dark }}
                    20px);
        }

        .header {
            padding: 40px;
            display: table;
            width: 100%;
            border-bottom: 1px solid #e2e8f0;
        }

        .logo-area {
            display: table-cell;
            vertical-align: middle;
        }

        .logo {
            max-height: 40px;
        }

        .invoice-tag {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
        }

        .tag {
            background:
                {{ $dark }}
            ;
            color: #fff;
            padding: 8px 16px;
            font-weight: bold;
            border-radius: 4px;
            display: inline-block;
        }

        .client-section {
            display: table;
            width: 100%;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
        }

        .client-box {
            display: table-cell;
            width: 50%;
            padding: 40px;
        }

        .client-box:first-child {
            border-right: 1px solid #e2e8f0;
        }

        .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 10px;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .value {
            font-size: 14px;
            font-weight: bold;
            color:
                {{ $dark }}
            ;
        }

        .small-text {
            font-size: 12px;
            color: #475569;
            margin-top: 4px;
            display: block;
        }

        .details-grid {
            display: table;
            width: 100%;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-item {
            display: table-cell;
            width: 33.33%;
            padding: 20px 40px;
            border-right: 1px solid #e2e8f0;
        }

        .detail-item:last-child {
            border-right: none;
        }

        .items-section {
            padding: 40px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            text-align: left;
            padding-bottom: 15px;
            border-bottom: 2px solid
                {{ $dark }}
            ;
            font-size: 10px;
            text-transform: uppercase;
            color:
                {{ $dark }}
            ;
        }

        .items-table td {
            padding: 20px 0;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .items-table .qty {
            text-align: center;
            color:
                {{ $primary }}
            ;
            font-weight: bold;
        }

        .items-table .price {
            text-align: right;
        }

        .total-section {
            padding: 0 40px 40px;
            display: table;
            width: 100%;
        }

        .spacer {
            display: table-cell;
            width: 50%;
        }

        .calc-box {
            display: table-cell;
            width: 50%;
        }

        .calc-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .calc-label {
            display: table-cell;
            color: #64748b;
        }

        .calc-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
        }

        .final-total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid
                {{ $dark }}
            ;
            font-size: 18px;
            color:
                {{ $primary }}
            ;
        }

        .footer-code {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background:
                {{ $dark }}
            ;
            color: #fff;
            padding: 15px 40px;
            font-family: monospace;
            font-size: 10px;
            text-align: right;
        }

        .qr-placeholder {
            float: left;
            width: 30px;
            height: 30px;
            background: #fff;
            opacity: 0.2;
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <div class="top-bar"></div>
    <div class="container">
        <div class="header">
            <div class="logo-area">
                @if($invoice->company->logo)
                    <img src="{{ public_path('storage/' . $invoice->company->logo) }}" class="logo">
                @else
                    <h2 style="color: {{ $dark }};">{{ $invoice->company->name }}</h2>
                @endif
            </div>
            <div class="invoice-tag">
                <div class="tag">INV-{{ $invoice->invoice_number }}</div>
            </div>
        </div>

        <div class="client-section">
            <div class="client-box">
                <div class="label">Payable To</div>
                <div class="value">{{ $invoice->company->name }}</div>
                @if($invoice->company->address)<span class="small-text">{{ $invoice->company->address }}</span>@endif
                @if($invoice->company->city)<span class="small-text">{{ $invoice->company->city }},
                {{ $invoice->company->state }}</span>@endif
                <span class="small-text">{{ $invoice->company->email }}</span>
            </div>
            <div class="client-box">
                <div class="label">Billed To</div>
                <div class="value">{{ $invoice->client->bill_to_name }}</div>
                @if($invoice->client->bill_to_contact)<div class="small-text">{{ $invoice->client->bill_to_contact }}</div>@endif
                @if($invoice->client->address)<span class="small-text">{{ $invoice->client->address }}</span>@endif
                <span class="small-text">{{ $invoice->client->email }}</span>
            </div>
        </div>

        <div class="details-grid">
            <div class="detail-item">
                <div class="label">Issued</div>
                <div class="value">{{ $invoice->issue_date->format('Y-m-d') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Due</div>
                <div class="value">{{ $invoice->due_date->format('Y-m-d') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Status</div>
                <div class="value" style="color: {{ $primary }}">{{ strtoupper($invoice->status) }}</div>
            </div>
        </div>

        <div class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th width="50%">Description</th>
                        <th width="15%" style="text-align: center">QTY</th>
                        <th width="20%" style="text-align: right">Unit Price</th>
                        <th width="15%" style="text-align: right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>
                                <div style="font-weight: bold; margin-bottom: 2px;">{{ $item->name }}</div>
                                <div style="color: #64748b; font-size: 10px;">{{ $item->description }}</div>
                            </td>
                            <td class="qty">{{ $item->quantity }}</td>
                            <td class="price">{{ $invoice->company->currency_symbol }}{{ number_format($item->price, 2) }}
                            </td>
                            <td class="price" style="font-weight: bold;">
                                {{ $invoice->company->currency_symbol }}{{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <div class="spacer">
                @if($invoice->notes)
                    <div style="padding-right: 40px;">
                        <div class="label">Note</div>
                        <div class="small-text">{{ $invoice->notes }}</div>
                    </div>
                @endif
            </div>
            <div class="calc-box">
                <div class="calc-row">
                    <div class="calc-label">Subtotal</div>
                    <div class="calc-value">
                        {{ $invoice->company->currency_symbol }}{{ number_format($invoice->subtotal, 2) }}</div>
                </div>
                <div class="calc-row">
                    <div class="calc-label">Tax</div>
                    <div class="calc-value">
                        {{ $invoice->company->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</div>
                </div>
                @if($invoice->discount_amount > 0)
                    <div class="calc-row">
                        <div class="calc-label">Discount</div>
                        <div class="calc-value">
                            -{{ $invoice->company->currency_symbol }}{{ number_format($invoice->discount_type === 'percentage' ? ($invoice->subtotal * $invoice->discount_amount / 100) : $invoice->discount_amount, 2) }}
                        </div>
                    </div>
                @endif
                <div class="calc-row final-total">
                    <div class="calc-label" style="color: {{ $dark }};">TOTAL DUE</div>
                    <div class="calc-value" style="color: {{ $primary }};">
                        {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</div>
                </div>
            </div>
        </div>

        @include('invoices.pdf.partials.signature')

        @if($invoice->company->invoice_footer && ($invoice->company->settings->show_invoice_footer ?? true))
            <div class="footer-code" style="text-align: left; text-transform: none;">{{ $invoice->company->invoice_footer }}</div>
        @endif

        <div class="footer-code">
            <div class="qr-placeholder"></div>
            SYSTEM GENERATED DOCUMENT : {{ $invoice->id }} : {{ date('Y-m-d H:i:s') }}
        </div>
    </div>
</body>

</html>