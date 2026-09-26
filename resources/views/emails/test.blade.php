<!DOCTYPE html>
<html>

<head>
    <title>Test Email</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>SMTP Configuration Test</h2>
    <p>Hello,</p>
    <p>This is a test email from <strong>{{ config('app.name') }}</strong> to verify that your SMTP settings are
        configured correctly.</p>
    <p>If you are reading this, the email sending functionality is working!</p>
    <br>
    <p>Best regards,<br>{{ config('app.name') }} Team</p>
</body>

</html>