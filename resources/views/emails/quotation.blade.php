<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation {{ $quotation->quotation_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Hello {{ $quotation->client->name }},</p>

    <p>Please find attached your quotation <strong>{{ $quotation->quotation_number }}</strong>.</p>

    <p>
        <strong>Total:</strong>
        {{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}
    </p>

    <p>
        You can also view it online here:
        <a href="{{ $quotation->share_url }}">{{ $quotation->share_url }}</a>
    </p>

    <p>This quotation is valid until {{ $quotation->valid_until->format('M d, Y') }}.</p>

    <p>Thank you,</p>
    <p>{{ $quotation->company->name }}</p>
</body>
</html>
