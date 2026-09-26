<!DOCTYPE html>
<html>

<head>
    <title>Welcome to {{ config('app.name') }}</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Welcome, {{ $user->name }}!</h2>

    <p>Thank you for registering with <strong>{{ config('app.name') }}</strong>. We're excited to have you on board.</p>

    <p>Your account has been successfully created. You can now log in and start managing your invoices and quotations.
    </p>

    <p>
        <a href="{{ route('dashboard') }}"
            style="background-color: #0ea5e9; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Go
            to Dashboard</a>
    </p>

    <p>If you have any questions or need assistance, feel free to contact our support team.</p>

    <br>
    <p>Best regards,<br>{{ config('app.name') }} Team</p>
</body>

</html>