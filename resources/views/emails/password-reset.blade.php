@php
    $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress');
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Reset Your Password</title>
</head>

<body
    style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div
        style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">{{ $siteName }}</h1>
    </div>

    <div style="background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px;">
        <h2 style="color: #1f2937; margin-top: 0;">Hi {{ $user->name }},</h2>

        <p>You recently requested to reset your password for your {{ $siteName }} account. Click the button below to
            reset it:</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}"
                style="background: #4f46e5; color: white; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Reset
                Password</a>
        </div>

        <p style="color: #6b7280; font-size: 14px;">If you did not request a password reset, please ignore this email.
            This link will expire in 60 minutes.</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

        <p style="color: #9ca3af; font-size: 12px; text-align: center;">
            If you're having trouble clicking the button, copy and paste this URL into your browser:<br>
            <a href="{{ $resetUrl }}" style="color: #6366f1; word-break: break-all;">{{ $resetUrl }}</a>
        </p>
    </div>
</body>

</html>