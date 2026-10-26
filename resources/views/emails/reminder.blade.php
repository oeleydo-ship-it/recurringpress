<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reminder['subject'] }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    {{-- Written by the user (optionally drafted with AI): always escaped, line breaks kept. --}}
    <div>{!! nl2br(e($reminder['body'])) !!}</div>

    <p style="margin-top: 24px;">
        <a href="{{ $invoice->share_url }}" style="display: inline-block; background: #4f46e5; color: #ffffff; padding: 10px 18px; border-radius: 6px; text-decoration: none;">View and pay invoice {{ $invoice->invoice_number }}</a>
    </p>
    <p style="font-size: 12px; color: #6b7280;">{{ $invoice->share_url }}</p>
</body>
</html>
