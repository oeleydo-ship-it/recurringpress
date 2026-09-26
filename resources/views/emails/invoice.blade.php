<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Hello {{ $invoice->client->name }},</p>

    <p>Please find attached your invoice <strong>{{ $invoice->invoice_number }}</strong>.</p>

    <p>
        <strong>Amount Due:</strong>
        {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}
    </p>

    <p>
        You can also view it online here:
        <a href="{{ $invoice->share_url }}">{{ $invoice->share_url }}</a>
    </p>

    <p>Thank you,</p>
    <p>{{ $invoice->company->name }}</p>
</body>
</html>
