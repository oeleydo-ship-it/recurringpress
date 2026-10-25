<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receipt->receipt_number }}</title>
    <style>
        @page { margin: 25px 35px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        
        /* Header */
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 3px solid #10b981; }
        .receipt-title { font-size: 28px; font-weight: bold; color: #10b981; margin-bottom: 5px; letter-spacing: 1px; }
        .receipt-number { font-size: 14px; color: #666; }
        
        /* Company Info */
        .company-info { text-align: center; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; color: #111; margin-bottom: 5px; }
        .company-details { font-size: 11px; color: #666; line-height: 1.4; }
        
        /* Amount Box - PROMINENT - Using solid color (DomPDF doesn't support gradients) */
        .amount-box { 
            background-color: #10b981;
            color: #ffffff; 
            padding: 20px; 
            margin: 20px 0; 
            text-align: center;
            border: 2px solid #059669;
        }
        .amount-label { 
            font-size: 12px; 
            color: #ffffff;
            margin-bottom: 5px; 
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .amount-value { 
            font-size: 36px; 
            font-weight: bold; 
            color: #ffffff;
        }
        .amount-status {
            font-size: 11px;
            color: #ffffff;
            margin-top: 8px;
            padding: 4px 12px;
            background-color: #059669;
            display: inline-block;
        }
        
        /* Details Grid using table for PDF compatibility */
        .details-table { width: 100%; margin-bottom: 20px; border-collapse: separate; border-spacing: 8px; }
        .details-cell { width: 50%; padding: 15px; vertical-align: top; background-color: #f9fafb; border: 1px solid #e5e7eb; }
        .details-label { font-size: 10px; color: #666; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.5px; }
        .details-value { font-size: 13px; color: #111; font-weight: bold; }
        .details-sub { font-size: 11px; color: #666; margin-top: 3px; }
        
        /* Payment Info Table */
        .payment-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .payment-table td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
        .payment-table .label { width: 40%; color: #666; background-color: #f9fafb; }
        .payment-table .value { font-weight: 600; color: #111; }
        
        /* Footer */
        .footer { margin-top: 25px; padding-top: 15px; border-top: 2px solid #10b981; text-align: center; }
        .thank-you { font-size: 16px; color: #10b981; font-weight: bold; margin-bottom: 8px; }
        .footer-text { font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="receipt-title">PAYMENT RECEIPT</div>
            <div class="receipt-number">{{ $receipt->receipt_number }}</div>
        </div>

        <!-- Company Info -->
        <div class="company-info">
            @if($receipt->company->logo_data_uri)
            <img src="{{ $receipt->company->logo_data_uri }}" alt="{{ $receipt->company->name }}" style="max-height: 50px; margin-bottom: 8px;">
            @endif
            <div class="company-name">{{ $receipt->company->name }}</div>
            <div class="company-details">
                @if($receipt->company->address){{ $receipt->company->address }}@endif
                @if($receipt->company->city), {{ $receipt->company->city }}@endif
                @if($receipt->company->state) {{ $receipt->company->state }}@endif
                @if($receipt->company->postal_code) {{ $receipt->company->postal_code }}@endif
                <br>
                @if($receipt->company->phone)Tel: {{ $receipt->company->phone }} | @endif
                @if($receipt->company->email){{ $receipt->company->email }}@endif
            </div>
        </div>

        <!-- AMOUNT BOX - Prominently Displayed -->
        <div class="amount-box">
            <div class="amount-label">Amount Received</div>
            <div class="amount-value">{{ $receipt->company->currency_symbol ?? '$' }}{{ number_format($receipt->amount, 2) }}</div>
            <div class="amount-status">✓ PAID</div>
        </div>

        <!-- Client & Date Details -->
        <table class="details-table">
            <tr>
                <td class="details-cell">
                    <div class="details-label">Received From</div>
                    <div class="details-value">{{ $receipt->client->name ?? 'N/A' }}</div>
                    @if($receipt->client && $receipt->client->email)
                    <div class="details-sub">{{ $receipt->client->email }}</div>
                    @endif
                    @if($receipt->client && $receipt->client->address)
                    <div class="details-sub">{{ $receipt->client->address }}</div>
                    @endif
                </td>
                <td class="details-cell">
                    <div class="details-label">Receipt Date</div>
                    <div class="details-value">{{ $receipt->issued_at->format('F d, Y') }}</div>
                    <div class="details-sub">{{ $receipt->issued_at->format('h:i A') }}</div>
                </td>
            </tr>
        </table>

        <!-- Payment Details Table -->
        <table class="payment-table">
            <tr>
                <td class="label">Payment Method</td>
                <td class="value">
                    @switch($receipt->payment->payment_method ?? $receipt->payment_method ?? 'manual')
                        @case('cash')
                            Cash
                            @break
                        @case('bank_transfer')
                            Bank Transfer
                            @break
                        @case('credit_card')
                            Credit Card
                            @break
                        @case('stripe')
                            Stripe (Online Payment)
                            @break
                        @case('paypal')
                            PayPal
                            @break
                        @case('manual')
                            Manual Payment
                            @break
                        @default
                            {{ ucfirst($receipt->payment->payment_method ?? $receipt->payment_method ?? 'Manual') }}
                    @endswitch
                </td>
            </tr>
            @if(($receipt->payment && $receipt->payment->transaction_id) || $receipt->transaction_id)
            <tr>
                <td class="label">Transaction ID</td>
                <td class="value">{{ $receipt->payment->transaction_id ?? $receipt->transaction_id }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Invoice Number</td>
                <td class="value">{{ $receipt->invoice->invoice_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Payment Date</td>
                <td class="value">{{ $receipt->payment->payment_date?->format('F d, Y') ?? $receipt->payment->created_at?->format('F d, Y') ?? $receipt->issued_at->format('F d, Y') }}</td>
            </tr>
        </table>

        @if($receipt->notes)
        <div style="background-color: #f9fafb; padding: 12px; margin-bottom: 15px; border: 1px solid #e5e7eb;">
            <div style="font-size: 10px; color: #666; text-transform: uppercase; margin-bottom: 5px;">Notes</div>
            <div style="color: #374151; font-size: 11px;">{{ $receipt->notes }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="thank-you">Thank You for Your Payment!</div>
            <div class="footer-text">
                This receipt confirms that we have received your payment.<br>
                Please keep this receipt for your records.
            </div>
            @if($receipt->company->tax_number)
            <div class="footer-text" style="margin-top: 10px;">
                Tax ID: {{ $receipt->company->tax_number }}
            </div>
            @endif
        </div>
    </div>
</body>
</html>

