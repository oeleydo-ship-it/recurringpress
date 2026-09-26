<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form Submission</title>
</head>

<body
    style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div
        style="background: linear-gradient(135deg, #0ea5e9, #0284c7); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">New Contact Form Submission</h1>
    </div>

    <div
        style="background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h2
                style="color: #1f2937; font-size: 18px; margin-top: 0; margin-bottom: 15px; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px;">
                Sender Information</h2>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #6b7280; width: 100px;"><strong>Name:</strong></td>
                    <td style="padding: 8px 0; color: #1f2937;">{{ $name }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;"><strong>Email:</strong></td>
                    <td style="padding: 8px 0; color: #1f2937;">
                        <a href="mailto:{{ $email }}" style="color: #0ea5e9; text-decoration: none;">{{ $email }}</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;"><strong>Subject:</strong></td>
                    <td style="padding: 8px 0; color: #1f2937;">{{ $subject }}</td>
                </tr>
            </table>
        </div>

        <div style="background: white; padding: 20px; border-radius: 8px;">
            <h2
                style="color: #1f2937; font-size: 18px; margin-top: 0; margin-bottom: 15px; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px;">
                Message</h2>
            <p style="color: #374151; white-space: pre-wrap; margin: 0;">{{ $messageContent }}</p>
        </div>

        <div
            style="margin-top: 20px; padding: 15px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
            <p style="margin: 0; color: #92400e; font-size: 14px;">
                <strong>Tip:</strong> You can reply directly to this email to respond to {{ $name }}.
            </p>
        </div>
    </div>

    <div style="text-align: center; padding: 20px; color: #6b7280; font-size: 12px;">
        <p style="margin: 0;">This message was sent from the RecurringPress contact form.</p>
    </div>
</body>

</html>