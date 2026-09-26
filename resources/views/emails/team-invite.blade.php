@php
    $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress');
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>You're invited</title>
</head>

<body
    style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div
        style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">{{ $siteName }}</h1>
    </div>

    <div style="background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px;">
        <h2 style="color: #1f2937; margin-top: 0;">Hi {{ $user->name }},</h2>

        <p>{{ $inviterName }} has invited you to join <strong>{{ $companyName }}</strong> on {{ $siteName }}.
            Choose a password to activate your account:</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $setupUrl }}"
                style="background: #4f46e5; color: white; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Set
                my password</a>
        </div>

        <p style="color: #6b7280; font-size: 14px;">This link expires in 60 minutes. If it has expired, use
            "Forgot password" on the login page with this email address ({{ $user->email }}).</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

        <p style="color: #9ca3af; font-size: 12px; text-align: center;">
            If you're having trouble clicking the button, copy and paste this URL into your browser:<br>
            <a href="{{ $setupUrl }}" style="color: #6366f1; word-break: break-all;">{{ $setupUrl }}</a>
        </p>
    </div>
</body>

</html>
