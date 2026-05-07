<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Inter', sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 20px; }
        .header { background: #000; color: #fff; padding: 20px; border-radius: 15px 15px 0 0; text-align: center; }
        .content { padding: 30px; }
        .footer { text-align: center; font-size: 12px; color: #888; padding: 20px; }
        .quote { background: #f9f9f9; padding: 15px; border-left: 4px solid #000; margin-bottom: 20px; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>HERD SHOP</h1>
        </div>
        <div class="content">
            <p>Dear {{ $replyData['name'] }},</p>
            <p>Thank you for reaching out to us. Regarding your message:</p>
            <div class="quote">
                "{{ $replyData['original_message'] }}"
            </div>
            <p><strong>Our Response:</strong></p>
            <p>{{ $replyData['reply_message'] }}</p>
            <br>
            <p>Best Regards,<br>Herd Shop Team</p>
        </div>
        <div class="footer">
            &copy; 2026 Herd Shop. All rights reserved.
        </div>
    </div>
</body>
</html>
